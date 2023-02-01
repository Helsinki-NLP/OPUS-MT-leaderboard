# -*-makefile-*-
#
# register scores in leaderboards
#  - create files to be added for each language pair and benchmark that we need to update
#  - files have extension 'unsorted.txt' and need to be merged with the leaderbaord file
#  - check top-level makefile about recipes to merge/update
#  - a flag will be created to mark that scores are registered
#

ALL_SOURCES      := $(filter-out lib,$(notdir ${shell find . -maxdepth 1 -mindepth 1 -type d}))
OPUSMT_SOURCES   := OPUS-MT-models Tatoeba-MT-models
EXTERNAL_SOURCES := $(filter-out ${OPUSMT_SOURCES},${ALL_SOURCES})

SOURCE     ?= facebook
MODEL_HOME ?= ${SOURCE}
MODEL      ?= nllb-200-distilled-600M

include lib/config.mk


## scores that need to be registered (stored in temporary score files)
## if REGISTER_ALL_SCORES is set: check all model directories
## if REGISTER_ALL_SCORES is not set: take only the current model dir

ifdef REGISTER_ALL_EXTERNAL
  MODEL_HOME      := .
  SCOREFILES      := ${wildcard ./*/*-scores.txt}
  SCOREFILES_DONE := ${SCOREFILES:.txt=.registered}
else ifdef REGISTER_ALL_SCORES
  SCOREFILES      := ${shell find ${MODEL_HOME}/ -name '*-scores.txt'}
  SCOREFILES_DONE := ${SCOREFILES:.txt=.registered}
else
  SCOREFILES      := ${wildcard ${MODEL_DIR}.*-scores.txt}
  SCOREFILES_DONE := ${SCOREFILES:.txt=.registered}
endif


.PHONY: all
all: register-scores
	${MAKE} upload-eval-files

## update all leader boards with all scores
.PHONY: register-scores
register-scores:
	${MAKE} REGISTER_ALL_SCORES=1 register

.PHONY: register-external
register-external:
	for s in ${EXTERNAL_SOURCES}; do \
	  ${MAKE} SOURCE=$$s register-scores; \
	done


## register scores in leaderboards
.PHONY: register
register: ${SCOREFILES_DONE}
	find ${MODEL_HOME}/ -name '*.txt' | xargs git add
	find ${MODEL_HOME}/ -name '*.registered' | xargs git add


## register the scores for the current model
## (scores will be added to some temporary files sorted by language pair and benchmark)
## NOTE: this removes langIDs from newstest sets to avoid confusion and duplicates

${MODEL_HOME}/%-scores.registered: ${MODEL_HOME}/%-scores.txt
	@echo "register scores from ${patsubst ${MODEL_HOME}/%,%,$<}"
	@cat $< | perl -e 'while (<>){ chomp; @a=split(/\t/); $$a[1]=~s/^(news.*)\-[a-z]{4}/$$1/; system "mkdir -p ${LEADERBOARD_DIR}/$$a[0]/$$a[1]"; open C,">>${LEADERBOARD_DIR}/$$a[0]/$$a[1]/$(patsubst .%,%,$(suffix $(basename $<))).$(subst /,.,${patsubst ${MODEL_HOME}/%,%,$<}).unsorted.txt"; $$m="$(shell cut -f5 $(basename $(basename $<)).scores.txt | head -1)";if ($$a[2] && $$m){print C "$$a[2]\t$$m\n";} close C; }'
	@touch $@


STORAGE_BUCKET = OPUS-MT-leaderboard

.PHONY: upload upload-eval-files
upload upload-eval-files:
	which a-get
	cd .. && find models/${MODEL_HOME} -name '*.eval.zip' | xargs swift upload ${STORAGE_BUCKET}
	swift post ${STORAGE_BUCKET} --read-acl ".r:*"

.PHONY: upload-all
upload-all:
	which a-get
	cd .. && find models/ -name '*.eval.zip' | xargs swift upload ${STORAGE_BUCKET}
	swift post ${STORAGE_BUCKET} --read-acl ".r:*"

.PHONY: upload-dryrun upload-eval-files-dryrun
upload-dryrun upload-eval-files-dryrun:
	@echo 'which a-get'
	@echo "cd .. && find models/${MODEL_HOME} -name '*.eval.zip' | xargs swift upload ${STORAGE_BUCKET}"
