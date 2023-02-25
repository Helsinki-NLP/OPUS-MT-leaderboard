#
# evaluate released Tatoeba MT models
# with existing benchmarks (collected in OPUS-MT-testsets)
#

PWD      := ${shell pwd}
REPOHOME := ${PWD}/../../


MODEL_STORAGE := https://object.pouta.csc.fi/Tatoeba-MT-models

ifndef MODEL_DISTS
ifneq ($(wildcard models.missing),)
  MODEL_DISTS := $(shell cat models.missing)
else
  MODEL_DISTS := ${shell wget -T 1 -q -O - ${MODEL_STORAGE}/index.txt | \
			grep '.zip$$' | grep -v '.eval.zip$$'}
endif
endif


MODELS          := $(patsubst %.zip,%,${MODEL_DISTS})
MODEL           ?= ${firstword ${MODELS}}
MODEL_DIST      ?= ${firstword ${MODEL_DISTS}}
MODEL_LANGPAIR   = ${firstword ${subst /, ,${MODEL}}}

MODEL_URL        = ${MODEL_STORAGE}/${MODEL}.zip
MODEL_EVAL_URL   = ${MODEL_URL:.zip=.eval.zip}


WORK_HOME ?= ${PWD}/work
WORK_DIR  ?= ${WORK_HOME}/${MODEL}

#-------------------------------------------------
# get supported source and target languages
#-------------------------------------------------

MODELINFO = ${WORK_DIR}/model/README.md
ifneq (${wildcard ${MODELINFO}},)
  SRC_LANGS = ${shell grep '\* source language(s)' ${MODELINFO} | cut -f2 -d: | xargs}
  TRG_LANGS = ${shell grep '\* valid language labels' ${MODELINFO} | cut -f2 -d: | tr '<>' '  ' | xargs}
ifeq (${words ${TRG_LANGS}},0)
  TRG_LANGS = ${shell grep '\* target language(s)' ${MODELINFO} | cut -f2 -d: | xargs}
endif
endif

#-------------------------------------------------
# all language pairs that the model supports
#-------------------------------------------------

MODEL_LANGPAIRS := $(sort ${MODEL_LANGPAIR} \
	${shell for s in ${SRC_LANGS}; do \
		for t in ${TRG_LANGS}; do echo "$$s-$$t"; done done})

include ../lib/config.mk
include ../lib/eval.mk

#-------------------------------------------------
## make all evaluation zip-files
#-------------------------------------------------

.PHONY: all
all: eval-models

all-reverse: eval-models-reverse-order

#-------------------------------------------------
## phony targets to evaluate only new models
## or only models that exist locally
## (no dependency on testset index)
#-------------------------------------------------

## check models that still need to be evaluated
## (i.e. *.eval.zip does not exist in storage)

.PNONY: print-eval-needed
print-eval-needed:
	@echo "$(shell ${WGET} -q -O - ${MODEL_STORAGE}/index.txt | \
		grep '.zip$$' | \
		sed 's/\.eval\.zip/.zip/' | sort | uniq -c | sed 's/^ *//' | \
		grep '^1 ' | cut -f2 -d' ')" | \
	tr ' ' "\n"

.PNONY: eval-new-models
eval-new-models:
	${MAKE} MODEL_DISTS="$(shell ${WGET} -q -O - ${MODEL_STORAGE}/index.txt | \
		grep '.zip$$' | \
		sed 's/\.eval\.zip/.zip/' | sort | uniq -c | sed 's/^ *//' | \
		grep '^1 ' | cut -f2 -d' ')" all


#-------------------------------------------------
# fetch model and get supported languages
#-------------------------------------------------

## fetch only the model
.PHONY: fetch-model
fetch-model: ${WORK_DIR}/model/decoder.yml

## fetch the model (either from local release dir or from the model storage)
${WORK_DIR}/model/decoder.yml:
	mkdir -p ${dir $@}
	if [ -e ${MODEL_HOME}/${MODEL_DIST} ]; then \
	  cp ${MODEL_HOME}/${MODEL_DIST} ${dir $@}model.zip; \
	else \
	  ${WGET} -q -O ${dir $@}model.zip ${MODEL_URL}; \
	fi
	unzip -d ${dir $@} ${dir $@}model.zip
## fix an old problem with the pre-process script
	mv ${dir $@}preprocess.sh ${dir $@}preprocess-old.sh
	sed -e 's#perl -C -pe.*$$#perl -C -pe  "s/(?!\\n)\\p{C}/ /g;" |#' \
	    -e 's#/projappl/project_2001569#$${HOME}/projappl#' \
	    -e 's#SPMENCODE=.*$$#SPMENCODE=`which spm_encode || echo "$${PWD}/tools/marian-dev/build/spm_encode"`#' \
		< ${dir $@}preprocess-old.sh > ${dir $@}preprocess.sh
	chmod +x ${dir $@}preprocess.sh




#-------------------------------------------------
# find models for which we miss some evaluation
#-------------------------------------------------

MODEL_EVAL_MISSING := $(patsubst %,%.missing,${ALL_LANGPAIRS})
.INTERMEDIATE: ${MODEL_EVAL_MISSING}

.PHONY: find-missing
find-missing: eval.missing models.missing

eval.missing: ${MODEL_EVAL_MISSING}
	find . -name '*.missing' | xargs cat | sort -u > $@

models.missing: eval.missing
	cut -f1 $< | sort -u > $@

${MODEL_EVAL_MISSING}:
	if [ -e ${LEADERBOARD_DIR}/$(@:.missing=)/model-list.txt ]; then \
	  for m in `grep 'Tatoeba-MT-models' ${LEADERBOARD_DIR}/$(@:.missing=)/model-list.txt`; do\
	    for t in $(sort $(basename $(filter-out %.labels,$(notdir $(wildcard ${TESTSET_HOME}/$(@:.missing=)/*.*))))); do \
	      for b in ${METRICS}; do \
	        if [ ! -f ${LEADERBOARD_DIR}/$(@:.missing=)/$$t/$$b-scores.txt ]; then \
	          echo "$$m	$$t	$$b" | sed 's#^.*MT-models/##' >> $@; \
	        elif [ `grep "$$m" ${LEADERBOARD_DIR}/$(@:.missing=)/$$t/$$b-scores.txt | wc -l` -eq 0 ]; then \
	          echo "$$m	$$t	$$b" | sed 's#^.*MT-models/##' >> $@; \
	        fi \
	      done \
	    done \
	  done \
	fi



#-------------------------------------------------
# create input file for translation
#-------------------------------------------------

.PHONY: input
input: ${WORK_DIR}/${TESTSET}.${LANGPAIR}.input


## more than one target language
## --> need target language labels
ifneq (${words ${TRG_LANGS}},1)
  USE_TARGET_LABELS = 1
else
  USE_TARGET_LABELS = 0
endif


ifneq (${wildcard ${WORK_DIR}}/model/preprocess.sh,)

## double-check whether the preprocessing script
## requires both language IDs or not
ifeq (${shell grep 'source-langid target-langid' ${WORK_DIR}/model/preprocess.sh 2>/dev/null | wc -l},1)
  USE_BOTH_LANGIDS = 1
endif

## take care of different calls to the pre-processing script
ifeq (${USE_BOTH_LANGIDS},1)
  PREPROCESS = ${WORK_DIR}/model/preprocess.sh ${SRC} ${TRG} ${WORK_DIR}/model/source.spm
else
  PREPROCESS = ${WORK_DIR}/model/preprocess.sh ${SRC} ${WORK_DIR}/model/source.spm
endif

endif


${WORK_DIR}/${TESTSET}.${LANGPAIR}.input: ${TESTSET_DIR}/${TESTSET}.${SRC}
	${PREPROCESS} < $< > $@
## check whether we need to replace the target language labels
ifeq (${USE_TARGET_LABELS},1)
ifneq (${wildcard ${TESTSET_DIR}/${TESTSET}.${TRG}.labels},)
	cut -f2- -d' ' $@ > $@.tmp1
	sed 's/^/>>/;s/$$/<</' < ${TESTSET_DIR}/${TESTSET}.${TRG}.labels > $@.tmp2
	paste -d' ' $@.tmp2 $@.tmp1 > $@
	rm -f $@.tmp2 $@.tmp1
endif
endif


#-------------------------------------------------
# create output file (translation)
#-------------------------------------------------

.PHONY: output
output: ${WORK_DIR}/${TESTSET}.${LANGPAIR}.output

${WORK_DIR}/${TESTSET}.${LANGPAIR}.output: ${WORK_DIR}/${TESTSET}.${LANGPAIR}.input
	if [ -e $< ]; then \
	  if [ -s $< ]; then \
	    ${LOAD_ENV} && ${MARIAN_DECODER} -i $< \
		-c ${WORK_DIR}/model/decoder.yml \
		${MARIAN_DECODER_FLAGS} |\
	    sed 's/ //g;s/▁/ /g' | sed 's/^ *//;s/ *$$//' > $@; \
	  fi \
	fi

