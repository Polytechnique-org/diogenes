# Horde .mo files makefile
#
# $Horde: horde/po/Makefile,v 1.2.2.3 2002/05/20 17:36:22 jan Exp $
#

APPLICATION = diogenes
MSGFMT = msgfmt --statistics -c -v -o
MSGFMTSOL = msgfmt -v -o
MSGMERGE = msgmerge -U
XGETTEXT = xgettext
XGETTEXT_OPTS = -j -k__ -o
PHPFILES = find htdocs include -name "*.php"


all: install

clearlocations:
	cd .. && ${XGETTEXT} --no-location ${XGETTEXT_OPTS} po/${APPLICATION}.pot `${PHPFILES}`
	cd .. && ${XGETTEXT} --add-location ${XGETTEXT_OPTS} po/${APPLICATION}.pot `${PHPFILES}`

xgettext:
	cd .. && ${XGETTEXT} ${XGETTEXT_OPTS} po/${APPLICATION}.pot `${PHPFILES}`

%.po: ${APPLICATION}.pot
	@echo "Updating .po files from .pot:"
	${MSGMERGE} $@ $<

install: *.po
	@echo "Checking for os ... ${OSTYPE}"; \
	if test "${OSTYPE}" = "solaris"; then \
		echo "You'll probably get some warnings on Solaris. This is normal."; \
	fi; \
	for LOCALE in `ls *.po | sed 's/\.[^.]*$$//g'`; do \
		if test $${LOCALE}.po = "messages.po"; then \
			continue; \
		fi; \
		echo "Compiling locale $${LOCALE}:"; \
		if ./shtool mkdir -p ../locale/$${LOCALE}/LC_MESSAGES; then \
			if test "${OSTYPE}" = "solaris"; then \
				if ${MSGFMTSOL} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo $${LOCALE}.po; then \
					echo "  ... done"; \
					echo; \
				else \
					echo "  ... FAILED"; \
					echo; \
				fi \
			else \
				if ${MSGFMT} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo $${LOCALE}.po; then \
					echo "  ... done"; \
					echo; \
				else \
					echo "  ... FAILED"; \
					echo; \
				fi \
			fi; \
		else \
			echo "Could not create locale directory for $${LOCALE}."; \
		fi \
	done;
