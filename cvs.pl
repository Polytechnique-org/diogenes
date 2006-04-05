#!/usr/bin/perl
#
# Custom wrapper around the CVS pserver for Diogenes
#
# Copyright 2003-2004 Jeremy Lainé
use strict;
use IPC::Open2;
use Socket;
use Fcntl qw(:DEFAULT :flock);
use Getopt::Std;
use POSIX qw(strftime waitpid WNOHANG WIFEXITED);

my $package = "cvs.pl";
my $version = "0.3";
my %opts;
my $daemon;


# get command-line arguments
sub init {
  if ( not getopts('dhp:r:fms:', \%opts) or $opts{'h'}) {
    &syntax();
    return 1;
  }

  # check we have a valid port
  if ($opts{'p'} !~ /^[0-9]+$/) {
    print("Error : no port or invalid port specified!\n");
    &syntax;
    return 1;
  }

  # check we have a cvsroot
  if (!$opts{'r'}) {
    print("Error : no CVS repository was specified!\n");
    &syntax;
    return 1;
  }
  $opts{'r'} =~ s/^(.*)\/$/$1/;
  if (! -d "$opts{'r'}/CVSROOT") {
    print("Error : no CVS repository found at $opts{'r'}\n");
    return 1;
  }

  # check we have a valid suicide delay
  if ($opts{'s'} !~ /^[0-9]*$/) {
    print("Error : invalid suicide delay!\n");
    &syntax;
    return 1;
  }

  return 0;
}


# debugging information
sub debug()
{
  if ($opts{'d'}) {
    &log(@_);
  }
}


# add a log entry
sub log()
{
  my $msg = shift;
  if (open(LOG,">> $opts{'r'}/CVSROOT/serverlog"))
  {
    my $hdr = strftime("%a %b %e %H:%M:%S",gmtime)." [$daemon]";
    if ($$ != $daemon) {
      $hdr .= "[$$]";
    }
    print LOG "$hdr $msg\n";
    close LOG;
  }
}


# add a user to the passwd file
sub pwdUser()
{
  my $user = shift;
  my $pwfile = $opts{'r'}."/CVSROOT/passwd";

  # read the password file, strip out current user
  my @lines;
  if (open(FH,"< $pwfile")) {
    @lines = <FH>;
    @lines = grep !/^$user(:.*)?(:*)?$/,@lines;
    close(FH);
  }

  # add user to password file
  my @pwuid = getpwuid($<);
  push @lines, $user . "::" . $pwuid[0] . "\n";
  sysopen(FH, $pwfile, O_WRONLY | O_CREAT)
    or die("Can't open passwd file!");
  flock(FH, LOCK_EX)
    or die("Can't lock passwd file!");
  truncate(FH,0);
  print FH @lines;
  close(FH);
  #die;
}


# the main loop of the server
sub serve()
{
  # make the socket
  socket(Server, PF_INET, SOCK_STREAM, getprotobyname('tcp'));

  # so we can restart our server quickly
  setsockopt(Server, SOL_SOCKET, SO_REUSEADDR, 1);

  # build socket address
  my $addr = sockaddr_in($opts{'p'}, 127.0.0.1);

  # bind and start listening
  if (!bind(Server, $addr) or !listen(Server,SOMAXCONN)) {
    print "Error : could not bind and listen to port $opts{'p'}!\n";
    close Server;
    exit(2);
  }


  # if necessary, fork to background
  if ($opts{'f'} && fork) {
    # close parent
    exit;
  }

  # store the PID of the parent process
  $daemon = $$;

  # select single-shot or full server
  if ($opts{'m'}) {
    &log("forked daemon bound");

    my $children = 0;
    # set up zombie reaper and suicide timer
    $SIG{CHLD} = sub {
      while (waitpid(-1,&WNOHANG) != -1) {
        &WIFEXITED($?) and $children--;
      }

      # if he have no more children, start suicide timer
      if (($$ == $daemon) && ($children == 0)) {
        alarm $opts{s};
      }
    };

    $SIG{ALRM} = sub {
      &log("reached inactivity limit of $opts{s} seconds, returning");
      exit;
    };

    # if set, start suicide timer
    alarm $opts{s};

    # full-blown server that forks for each request
    for (my $conn = 0;; $conn++) {
      # we can get interrupted system calls
      # ignore these and loop back
      if (!accept(Client,Server)) {
        next;
      }

      # stop inactivity timer
      alarm 0;

      &debug("forking child to handle request");

      if (my $cpid = fork) {

        # parent process, closes unused handle
        $children++;
        close Client;

      } elsif (defined $cpid) {

        # child process
        close Server;
        &serveClient(\*Client);
        close Client;
        exit;

      } else {
        print "Could not fork serveClient!";
        exit(2);
      }
    }
    close Server;

  } else {

    &log("single-request daemon bound");
    # accepts a single connection, then returns
    accept(Client,Server);
    close Server;
    &serveClient(\*Client);

  }

  &log("returning");
}


# wraps around an instance of "cvs pserver"
sub serveClient()
{
  my $client = shift;
  my $cvsroot = $opts{'r'};

  # begin auth
  chomp(my $in = <$client>);
  if ($in !~ /^BEGIN AUTH REQUEST$/) {
    &log("client did not send BEGIN AUTH REQUEST, returning!");
    close $client;
    return;
  }
  chomp($in = <$client>);
  if ($in !~ /^$cvsroot$/) {
    close $client;
    return;
  }
  # user name
  chomp(my $user = <$client>);

    # password, discarded
  <$client>;
  # end auth
  chomp($in = <$client>);
  if ($in !~ /^END AUTH REQUEST$/) {
    close $client;
    return;
  }

  # add the user to the passwd file
  &log("authenticated $user");
  &pwdUser($user);


  select($client);
  $| = 1;


  local(*Reader,*Writer);

  # create bidirectional pipe
  if ( !open2(\*Reader, \*Writer, "cvs -f --allow-root $cvsroot pserver") ) {
    close $client;
    return;
  }

  print Writer "BEGIN AUTH REQUEST\n$cvsroot\n$user\nA\nEND AUTH REQUEST\n";

  # process input and output
  if (my $pid = fork) {
    close Reader;

    # process client input
    while (my $inn = <$client>)
    {
      print Writer $inn;
    }

    close Client;
    close Writer;

  } elsif (defined $pid) {
    close Writer;

    # feed output back to client
    while (my $out = <Reader>)
    {
      print $client $out;
    }

    close Reader;
    close Client;
    exit;

  } else {
    die("Could not fork!");
  }

}


# display program syntax
sub syntax {
  print "\n",
  "[ This is $package (v$version), Diogenes's wrapper around the CVS pserver ]\n\n",
  "Syntax:\n",
  "  $package (-h | -p <port> -r <cvsroot>) [options]\n\n",
  " Help:\n",
  "  -h           - display this help message\n",
  " Required:\n",
  "  -p <port>    - listen on port <port>\n",
  "  -r <cvsroot> - use the local CVS repository <cvsroot>\n",
  " Options:\n",
  "  -d           - debugging mode\n",
  "  -f           - fork to background\n",
  "  -m           - serve multiple client requests instead of dying after first client\n",
  "  -s <seconds> - suicide after <seconds> seconds of inactivity\n";
}


# execute program
if (&init()) {
  exit(1);
} else {
  &serve();
  exit(0);
}
