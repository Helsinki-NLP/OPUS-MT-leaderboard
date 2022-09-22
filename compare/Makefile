#
# create files that list all translations for all models on the leaderboard
# for a given metric (comet as default)
#

METRIC     ?= bleu

RELEASE_PACKAGE = Tatoeba-MT-models
STORAGE_HOME    = https://object.pouta.csc.fi/${RELEASE_PACKAGE}

LANGPAIRS  := $(notdir ${wildcard ../scores/*-*})
TRANSFILES := $(patsubst %,%.translations.gz,${LANGPAIRS})

LANGPAIR   ?= eng-deu
SCOREFILES := $(wildcard ../scores/${LANGPAIR}/*/${METRIC}-scores.txt)
BENCHMARKS := $(patsubst ../scores/${LANGPAIR}/%/${METRIC}-scores.txt,%,${SCOREFILES})

ifneq (${SCOREFILES},)
  MODELS_ZIP     := $(sort $(shell cut -f2 ${SCOREFILES}))
  MODELS_EVALDIR := $(patsubst %.zip,${LANGPAIR}/%.eval.d,$(notdir ${MODELS_ZIP}))
endif



.PHONY: all
all: ${TRANSFILES}

.PHONY: fetch
fetch: ${MODELS_EVALDIR}

info:
	@echo ${MODELS_EVALDIR}
	@echo "../scores/${LANGPAIR}/*/${METRIC}-scores.txt"

.PHONY: cleanup
cleanup:
	rm -f ${LANGPAIR}/*.eval.d/*
	-rmdir ${LANGPAIR}/*.eval.d
	-rmdir ${LANGPAIR}
	if [ -e ${LANGPAIR}.translations.gz ]; then \
	  if [ `zcat ${LANGPAIR}.translations.gz | wc -l` -eq 0 ]; then \
	    rm -f ${LANGPAIR}.translations.gz; \
	  fi \
	fi


%.eval.zip:
	mkdir -p ${dir $@}
	-wget -O $@ ${STORAGE_HOME}/$@

%.eval.d: %.eval.zip
	mkdir -p ${dir $@}
	-unzip $< -d $@; \

%.translations.gz:
	${MAKE} LANGPAIR=$(@:.translations.gz=) fetch
	./merge-translations.pl $(@:.translations.gz=)/*/*.compare | gzip -c > $@
	${MAKE} LANGPAIR=$(@:.translations.gz=) cleanup


MODEL_CONTAINER = OPUS-MT-translations

upload:
	swift upload ${MODEL_CONTAINER} --changed --skip-identical *.translations.gz

link-list:
	ls *.translations.gz | sort |\
	sed 's#^\(.*\)$$#* [\1](https://object.pouta.csc.fi/${MODEL_CONTAINER}/\1)/#'

