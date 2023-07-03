# -*-makefile-*-


REPOHOME := $(dir $(lastword ${MAKEFILE_LIST}))
MAKEDIR  := ${REPOHOME}build/


.PHONY: all
all: scores
	find scores -name '*unsorted*' -empty -delete
	${MAKE} -s updated-leaderboards
	${MAKE} -s scores/langpairs.txt scores/benchmarks.txt
	@git add scores/langpairs.txt scores/benchmarks.txt


.PHONY: all-langpairs
all-langpairs:
	@find scores -name '*unsorted*' -empty -delete
	${MAKE} -s refresh-leaderboards
	${MAKE} -s scores/langpairs.txt scores/benchmarks.txt
	@git add scores/langpairs.txt scores/benchmarks.txt


include ${MAKEDIR}leaderboards.mk
include ${MAKEDIR}config.mk
