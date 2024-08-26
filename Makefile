# -*-makefile-*-


OVERVIEW_FILES := scores/langpairs.txt \
		scores/benchmarks.txt \
		models/modelsize.txt \
		released-models.txt \
		release-history.txt


.PHONY: all
all: scores/bleu_scores.db
	${MAKE} -C models register-all
	${MAKE} -s overview-files
	${MAKE} update-git

#	${MAKE} -C models all
##	${MAKE} -C models register-all
##	${MAKE} -C models upload-all


.PHONY: overview-files
overview-files: $(OVERVIEW_FILES)

update-git:
	git add $(OVERVIEW_FILES)
	git ls-files -o --exclude-standard > untracked-files.txt
	grep '^scores/.*\.txt$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.txt$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.info$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.readme$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.registered$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.output$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.eval$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.logfiles$$' untracked-files.txt | xargs -n 500 git add
	grep '^models/.*\.zip$$' untracked-files.txt \
	| grep -v '.eval.zip' | grep -v 'model.zip' | xargs -n 500 git add
	rm -f untracked-files.txt
	git commit -am 'scores tables updated'

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


# old style: need to update all kind of score files

.PHONY: all-files
all-files: scores
	find scores -name '*unsorted*' -empty -delete
	${MAKE} -s updated-leaderboards
	${MAKE} -s overview-files

# old style: update score files (like above but for many language pairs)

.PHONY: all-langpairs
all-langpairs:
	@find scores -name '*unsorted*' -empty -delete
	${MAKE} -s refresh-leaderboards
	${MAKE} -s overview-files


