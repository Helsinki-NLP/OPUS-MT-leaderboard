# -*-makefile-*-


REPOHOME := $(dir $(lastword ${MAKEFILE_LIST}))
MAKEDIR  := ${REPOHOME}build/

OVERVIEW_FILES := scores/langpairs.txt scores/benchmarks.txt released-models.txt release-history.txt


.PHONY: all
all: scores
	find scores -name '*unsorted*' -empty -delete
	${MAKE} -s updated-leaderboards
	${MAKE} -s overview-files


.PHONY: all-langpairs
all-langpairs:
	@find scores -name '*unsorted*' -empty -delete
	${MAKE} -s refresh-leaderboards
	${MAKE} -s overview-files


.PHONY: overview-files
overview-files: $(OVERVIEW_FILES)
	@git add $^


include ${MAKEDIR}leaderboards.mk
include ${MAKEDIR}config.mk
