# definitions

VERSION = 0.9.20
PKG_DIST = diogenes-$(VERSION)
LIB_DIST = libdiogenes-$(VERSION)

PKG_FILES = AUTHORS ChangeLog COPYING README Makefile \
            cvs.pl Doxyfile.in
	    
PKG_DIRS = config htdocs include locale po plugins scripts styles templates

LIB_FILES = COPYING
LIB_BASE = include/diogenes

VCS_FILTER = \( -name .arch-ids -o -name CVS -o -name .svn \) -prune

# global targets

build: pkg-build lib-build

dist: clean pkg-dist lib-dist

clean:
	rm -rf locale include/diogenes.globals.inc.php
	for ext in php tpl css po; \
	do \
	  find -type f -name *.$$ext~ -exec rm -f {} \; ; \
	done

%: %.in Makefile
	sed -e 's,@VERSION@,$(VERSION),g' $< > $@


# diogenes package targets

pkg-build: include/diogenes.globals.inc.php Doxyfile
	make -C po

pkg-dist: pkg-build
	rm -rf $(PKG_DIST) $(PKG_DIST).tar.gz
	mkdir $(PKG_DIST)
	cp -a $(PKG_FILES) $(PKG_DIST)
	for dir in `find $(PKG_DIRS) $(VCS_FILTER) -o -type d -print`; \
	do \
          mkdir -p $(PKG_DIST)/$$dir; \
	  find $$dir -maxdepth 1 -type f -exec cp {} $(PKG_DIST)/$$dir \; ; \
	done
	tar czf $(PKG_DIST).tar.gz $(PKG_DIST)
	rm -rf $(PKG_DIST)


# diogenes library targets

lib-build:

lib-dist: lib-build
	rm -rf $(LIB_DIST)
	mkdir $(LIB_DIST)
	cp -a $(LIB_FILES) $(LIB_DIST)
	for dir in `cd $(LIB_BASE) && find . $(VCS_FILTER) -o -type d -print`; \
	do \
          mkdir -p $(LIB_DIST)/$$dir; \
	  find $(LIB_BASE)/$$dir -maxdepth 1 -type f -exec cp {} $(LIB_DIST)/$$dir \; ; \
	done
	tar czf $(LIB_DIST).tar.gz $(LIB_DIST)
	rm -rf $(LIB_DIST)


.PHONY: build dist clean pkg-build pkg-dist lib-build lib-dist

