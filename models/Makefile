# -*-makefile-*-
#
# register scores in leaderboards
#  - create files to be added for each language pair and benchmark that we need to update
#  - files have extension 'unsorted.txt' and need to be merged with the leaderbaord file
#  - check top-level makefile about recipes to merge/update
#  - a flag will be created to mark that scores are registered
#


MODEL_HOME ?= facebook
MODEL      ?= nllb-200-distilled-600M

include lib/config.mk


## scores that need to be registered (stored in temporary score files)
## if REGISTER_ALL_SCORES is set: check all model directories
## if REGISTER_ALL_SCORES is not set: take only the current model dir

ifdef REGISTER_ALL_SCORES
  SCOREFILES      := ${shell find ${MODEL_HOME}/ -name '*-scores.txt'}
  SCOREFILES_DONE := ${SCOREFILES:.txt=.registered}
else
  SCOREFILES      := ${wildcard ${MODEL_DIR}.*-scores.txt}
  SCOREFILES_DONE := ${SCOREFILES:.txt=.registered}
endif


.PHONY: all
all: update-leaderboards

## update all leader boards with all scores
.PHONY: update-leaderboards
update-leaderboards:
	${MAKE} REGISTER_ALL_SCORES=1 register-scores

## register scores in leaderboards
.PHONY: register-scores
register-scores: ${SCOREFILES_DONE}
	find ${MODEL_HOME}/ -name '*.txt' | xargs git add
	find ${MODEL_HOME}/ -name '*.registered' | xargs git add


## register the scores for the current model
## (scores will be added to some temporary files sorted by language pair and benchmark)
## NOTE: this removes langIDs from newstest sets to avoid confusion and duplicates

${MODEL_HOME}/%-scores.registered: ${MODEL_HOME}/%-scores.txt
	@echo "register scores from ${patsubst ${MODEL_HOME}/%,%,$<}"
	@cat $< | perl -e 'while (<>){ chomp; @a=split(/\t/); $$a[1]=~s/^(news.*)\-[a-z]{4}/$$1/; system "mkdir -p ${LEADERBOARD_DIR}/$$a[0]/$$a[1]"; open C,">>${LEADERBOARD_DIR}/$$a[0]/$$a[1]/$(patsubst .%,%,$(suffix $(basename $<))).$(subst /,.,${patsubst ${MODEL_HOME}/%,%,$<}).unsorted.txt"; print C "$$a[2]\t$(shell cut -f5 $(basename $(basename $<)).scores.txt | head -1)\n"; close C; }'
	@touch $@


upload-eval-files:
	which a-get
	cd ${MODEL_HOME} && find . -name '*.eval.zip' | xargs swift upload ${MODEL_HOME}
	swift post ${MODEL_HOME} --read-acl ".r:*"


upload-eval-files-dryrun:
	@echo 'which a-get'
	@echo "cd ${MODEL_HOME} && find . -name '*.eval.zip' | xargs swift upload ${MODEL_HOME}"
