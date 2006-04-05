icondef = gartoon.defs
conv = sodipodi

include .deps

all: $(ICONS) 

.deps: Makefile $(icondef)
	echo "boot: all" > .deps
	echo >> .deps
	rawicons=`cat $(icondef) | cut -f1`; \
	allicons=""; \
	for icon in $$rawicons; do \
	  allicons="$$allicons $$icon.png"; \
	  isrc=`cat $(icondef) | grep ^$$icon | cut -f2`; \
	  echo "$$icon.png: $$isrc" >> .deps; \
	  echo "	$(conv) -z --file=$$isrc --export-png=$$icon.png --export-width=24 --export-height=24" >> .deps; \
	  echo >> .deps; \
	done; \
	echo "ICONS=$$allicons" >> .deps; \

.PHONY: all deps

