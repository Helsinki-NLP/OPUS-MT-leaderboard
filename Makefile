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

update-git:
	git add $(OVERVIEW_FILES)
	find scores -type f -name '*.txt' | xargs git add
	find models -type f -name '*.txt' | xargs git add
	find models -type f -name '*.registered' | xargs git add
	find models -type f -name '*.output' | xargs git add
	find models -type f -name '*.eval' | xargs git add
	find models -type f -name '*.logfiles' | xargs git add
	find models -type f -name '*.zip' | grep -v '.eval.zip' | xargs git add

include ${MAKEDIR}leaderboards.mk
include ${MAKEDIR}config.mk

