#
# evaluate released Tatoeba MT models
# with existing benchmarks (collected in OPUS-MT-testsets)
#


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
MODEL_DIST      ?= ${MODEL}.zip
MODEL_LANGPAIR   = ${firstword ${subst /, ,${MODEL}}}

PWD             := ${shell pwd}
MODEL_HOME      := ${PWD}
MODEL_URL        = ${MODEL_STORAGE}/${MODEL}.zip
MODEL_YAML       = ${MODEL_STORAGE}/${MODEL}.yml
MODEL_EVAL_URL   = ${MODEL_URL:.zip=.eval.zip}


WORK_HOME ?= ${PWD}/work
WORK_DIR  ?= ${WORK_HOME}/${MODEL}

#-------------------------------------------------
# get supported source and target languages
#-------------------------------------------------

MODELINFO = ${WORK_DIR}/model/README.md
ifneq (${wildcard ${MODELINFO}},)
  SRC_LANGS := ${shell grep '\* source language(s)' ${MODELINFO} | cut -f2 -d: | xargs}
  TRG_LANGS := ${shell grep '\* valid language labels' ${MODELINFO} | cut -f2 -d: | tr '<>' '  ' | xargs}
ifeq (${words ${TRG_LANGS}},0)
  TRG_LANGS := ${shell grep '\* target language(s)' ${MODELINFO} | cut -f2 -d: | xargs}
endif
else
  SRC_LANGS := $(shell wget -qq -O - ${MODEL_YAML} | yq '.source-languages' | cut -f2 -d' ')
  TRG_LANGS := $(shell wget -qq -O - ${MODEL_YAML} | yq '.target-languages' | cut -f2 -d' ')
endif


#-------------------------------------------------
# all language pairs that the model supports
#-------------------------------------------------

MODEL_LANGPAIRS := $(sort ${MODEL_LANGPAIR} \
	${shell for s in ${SRC_LANGS}; do \
		for t in ${TRG_LANGS}; do echo "$$s-$$t"; done done})


include ../../build/config.mk
include ../../build/eval.mk

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


#-------------------------------------------------
# create input file
#
# prepare data for translation (subword tokenization)
# check whether we need to replace the target language labels
# if there are specific labels per test set instance
#-------------------------------------------------

.PHONY: input
input: ${SYSTEM_INPUT}

${SYSTEM_INPUT}: ${TESTSET_SRC}
	@${PREPROCESS} < $< > $@
## check whether we need to replace the target language labels
ifeq (${USE_TARGET_LABELS},1)
ifneq ($(TESTSET_LABELS),)
	@if [ -e $(TESTSET_LABELS) ]; then \
	  cut -f2- -d' ' $@                          > $@.tmp1; \
	  sed 's/^/>>/;s/$$/<</' < $(TESTSET_LABELS) > $@.tmp2; \
	  paste -d' ' $@.tmp2 $@.tmp1                > $@; \
	  rm -f $@.tmp2 $@.tmp1; \
	fi
endif
endif


#-------------------------------------------------
# create output file (translation)
#-------------------------------------------------

.PHONY: output
output: ${SYSTEM_OUTPUT}

${SYSTEM_OUTPUT}: ${TESTSET_SRC}
	@echo "... create $(MODEL)/$(notdir $@)"
	@mkdir -p $(dir $@)
	@mkdir -p ${MODEL_DIR}
	@${MAKE} -s ${SYSTEM_INPUT}
	@echo "Translating sentences from $(notdir $<) to ${TRG}"          > $@.log
	@echo ""                                                          >> $@.log
	@if [ -s ${SYSTEM_INPUT} ]; then \
	    echo " - size of sentences to translate:"                     >> $@.log; \
	    cat $< | wc -l                                                >> $@.log; \
	    echo " - size of sentences to translate (subword tokenized):" >> $@.log; \
	    cat ${SYSTEM_INPUT} | wc                                      >> $@.log; \
	    ${LOAD_ENV} && ${MONITOR} ${MARIAN_DECODER} \
		-i ${SYSTEM_INPUT} \
		-c ${WORK_DIR}/model/decoder.yml \
		${MARIAN_DECODER_FLAGS}                                  2>> $@.log |\
	    sed 's/ //g;s/▁/ /g' | sed 's/^ *//;s/ *$$//' > $@; \
	fi
	@mv $@.log $(patsubst %.output,%.log,$@)
	@rm -f ${SYSTEM_INPUT}


