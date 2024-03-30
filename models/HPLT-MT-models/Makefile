
MODEL_STORAGE  := https://object.pouta.csc.fi/HPLT-MT-models
MODEL_HF_DIR   := https://huggingface.co/HPLT
MODEL_HPLT_GIT := https://raw.githubusercontent.com/hplt-project/HPLT-MT-Models

TRG_LANGUAGES  := ar bs ca et eu fi ga gl hi hr is mt nn sq sw zh_hant
LANGPAIRS      := $(foreach l,${TRG_LANGUAGES},$(l)-en en-$(l))

MODEL_VERSION  := v1.0
MODEL_VARIANTS := hplt hplt_opus


MODELS := $(foreach m,$(MODEL_VARIANTS),$(foreach l,$(LANGPAIRS),$(l)/translate-$(l)-${MODEL_VERSION}-$(m)))
MODEL  ?= ${firstword ${MODELS}}


MODEL_INFO_FILE  := $(patsubst %,%.info,${MODEL})
MODEL_INFO_FILES := $(patsubst %,%.info,${MODELS})


PWD        := ${shell pwd}
MODEL_HOME := ${PWD}
MODEL_URL  := ${MODEL_HF_DIR}/$(notdir ${MODEL})
# MODEL_URL  := HPLT-MT-models/${MODEL}


WORK_HOME  := ${PWD}/work
WORK_DIR   := ${WORK_HOME}/${MODEL}


#-------------------------------------------------
# get supported source and target languages
#-------------------------------------------------

# original model languages in 2-letter codes

MODEL_SRCLANG   := $(word 2,$(subst -, ,$(notdir ${MODEL})))
MODEL_TRGLANG   := $(word 3,$(subst -, ,$(notdir ${MODEL})))
MODEL_LANGPAIR  := $(MODEL_SRCLANG)-$(MODEL_TRGLANG)


# languages in 3-letter codes
# (note that all variables can include more than one value)

SRC_LANGS       := $(shell iso639 -3 $(MODEL_SRCLANG))
TRG_LANGS       := $(shell iso639 -3 $(MODEL_TRGLANG))
MODEL_LANGPAIRS := $(SRC_LANGS)-$(TRG_LANGS)



include ../../build/config.mk
include ../../build/eval.mk

#-------------------------------------------------
## make all evaluation zip-files
#-------------------------------------------------

.PHONY: all
all: eval-models

#-------------------------------------------------
# create model-info-file
#-------------------------------------------------

.PHONY: model-info
model-info: ${MODEL_INFO_FILE}

.PHONY: all-model-infos
all-model-infos: ${MODEL_INFO_FILES}

${MODEL_INFO_FILES}: %.info:
	mkdir -p $@.d
	-wget -qq -O $@.d/model.npz ${MODEL_HF_DIR}/$(notdir ${MODEL})/resolve/main/model.npz.best-chrf.npz?download=true
	-../../tools/model_info.py --summary --model $@.d/*.npz > $@
	echo "source languages: ${SRC_LANGS}" >> $@
	echo "target languages: ${TRG_LANGS}" >> $@
	echo "nr_source languages: 1" >> $@
	echo "nr_target languages: 1" >> $@
	rm -fr $@.d

#-------------------------------------------------
# fetch model and get supported languages
#-------------------------------------------------


## fetch only the model
.PHONY: fetch-model
fetch-model: ${WORK_DIR}/model/model.npz.best-chrf.npz

## fetch the model (either from local release dir or from the model storage)
${WORK_DIR}/model/model.npz.best-chrf.npz: ${MODEL_INFO_FILE}
	mkdir -p ${dir $@}
	wget -qq -O $@ ${MODEL_HF_DIR}/$(notdir ${MODEL})/resolve/main/model.npz.best-chrf.npz?download=true
	wget -qq -O $(dir $@)model.spm ${MODEL_HF_DIR}/$(notdir ${MODEL})/resolve/main/model.${MODEL_LANGPAIR}.spm?download=true
#	wget -qq -O $(dir $@)decoder.yml ${MODEL_HPLT_GIT}/main/v1.0/inference/inference_config.yml


.PHONY: remove-model
remove-model:
	rm -fr ${WORK_DIR}/model


#-------------------------------------------------
# create output file (translation)
#-------------------------------------------------

.PHONY: output
output: ${SYSTEM_OUTPUT}

${SYSTEM_OUTPUT}: ${TESTSET_SRC}
	@${MAKE} -s ${WORK_DIR}/model/model.npz.best-chrf.npz
	@echo "... create $(MODEL)/$(notdir $@)"
	@mkdir -p $(dir $@) ${MODEL_DIR}
	@echo "Translating sentences from $(notdir $<) to ${TRG}"          > $@.log
	@echo ""                                                          >> $@.log
	if [ -s ${TESTSET_SRC} ]; then \
	    echo " - nr of sentences to translate:"                       >> $@.log; \
	    cat $< | wc -l                                                >> $@.log; \
	    echo " - size of testset to translate:"                       >> $@.log; \
	    cat ${TESTSET_SRC} | wc                                       >> $@.log; \
	    ${LOAD_ENV} && ${MONITOR} ${MARIAN_DECODER} \
		--input ${TESTSET_SRC} \
		--models ${WORK_DIR}/model/model.npz.best-chrf.npz \
		--vocabs ${WORK_DIR}/model/model.spm ${WORK_DIR}/model/model.spm \
		${MARIAN_DECODER_FLAGS}                             > $@ 2>> $@.log; \
	fi
	@mv $@.log $(patsubst %.output,%.log,$@)


# --config ${WORK_DIR}/model/decoder.yml \
