# -*-makefile-*-


OVERVIEW_FILES := scores/langpairs.txt scores/benchmarks.txt models/modelsize.txt \
		released-models.txt release-history.txt


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

update-git:
	git add $(OVERVIEW_FILES)
	git ls-files -o --exclude-standard > untracked-files.txt
	grep '^scores/.*\.txt$$' untracked-files.txt | xargs -n 1000 git add
	grep '^models/.*\.txt$$' untracked-files.txt | xargs -n 1000 git add
	grep '^models/.*\.registered$$' untracked-files.txt | xargs -n 1000 git add
	grep '^models/.*\.output$$' untracked-files.txt | xargs -n 1000 git add
	grep '^models/.*\.eval$$' untracked-files.txt | xargs -n 1000 git add
	grep '^models/.*\.logfiles$$' untracked-files.txt | xargs -n 1000 git add
	grep '^models/.*\.zip$$' untracked-files.txt | grep -v '.eval.zip' | xargs -n 1000 git add
	rm -f untracked-files.txt

## the commands below become much too slow with many files 
##
#	find scores -type f -name '*.txt' | xargs -n 1000 git add
#	find models -type f -name '*.txt' | xargs -n 1000 git add
#	find models -type f -name '*.registered' | xargs -n 1000 git add
#	find models -type f -name '*.output' | xargs -n 1000 git add
#	find models -type f -name '*.eval' | xargs -n 1000 git add
#	find models -type f -name '*.logfiles' | xargs -n 1000 git add
#	find models -type f -name '*.zip' | grep -v '.eval.zip' | xargs -n 1000 git add


include build/leaderboards.mk
include build/config.mk


fix-errors:
	make -C models register-all
	make all-langpairs
