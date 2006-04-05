#!/usr/bin/perl

use strict;
my $revision = '$Revision: 1.1 $';


# compare two directories recursively
sub scandir {
  my ($rRoot,$oRoot,$rPat,$oPat,$curdir) = @_;
  
  opendir(SPOOL,"$rRoot/$curdir") || die "can't open '$rRoot/$curdir'";
  my @entries = grep { ! /^\.{1,2}$/ } readdir SPOOL;
  closedir SPOOL;

  my @children;
  
  # process entries
  foreach my $entry(@entries) {
    my $fentry = $curdir ? "$curdir/$entry" : $entry;
    if ( -d "$rRoot/$fentry" ) {
      if ( -d "$oRoot/$fentry" ) {
        push @children,$fentry;
      } else {
        print "[d] $fentry\n";
      }
    } else {
      $fentry =~ s/$rPat/$oPat/;
      ( -f "$oRoot/$fentry") or
        print "[f] $fentry\n";
    }
  }

  # recurse
  foreach my $child(@children) {
    &scandir($rRoot,$oRoot,$rPat,$oPat,$child);
  }
}


# display usage
sub syntax {
  $revision =~ s/(\$)Revision: (.*) \$$/v\2/;

  print "[ This is checkspool.pl ($revision), a checker for Diogenes's spool ]\n\n",
        "Syntax:\n",
        "  checkspool.pl <rcs> <spool>\n\n",
        " Arguments:\n",
        "  rcs       - the RCS root\n",
        "  spool     - the spool directory\n",
        "\n";
}


# the main routine
sub main {

  if (@ARGV < 2)  {
    &syntax;
    exit(1);
  }

  my $rcs = $ARGV[0];
  my $spool = $ARGV[1];

  # forward scan
  print "\nNot in RCS (ignore templates_c) :\n";
  &scandir($spool,$rcs,'$',',v');

  # reverse scan
  print "\nNot in spool :\n";
  &scandir($rcs,$spool,',v$','');
}

&main
