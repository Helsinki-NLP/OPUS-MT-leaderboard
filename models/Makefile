# -*-makefile-*-
#
# register scores in leaderboards
#  - create files to be added for each language pair and benchmark that we need to update
#  - files have extension 'unsorted.txt' and need to be merged with the leaderbaord file
#  - check top-level makefile about recipes to merge/update
#  - a flag will be created to mark that scores are registered
#

PWD      := ${shell pwd}
REPOHOME := ${PWD}/../


SOURCE      ?= Tatoeba-MT-models
MODEL_HOME  ?= ${SOURCE}
MODEL       ?= deu-eng/opus-2021-02-22


.PHONY: all
all: register-all
	${MAKE} upload-all

.PHONY: register-opusmt
register-opusmt: register-opus-mt register-tatoeba-mt

.PHONY: register-opus-mt
register-opus-mt:
	${MAKE} SOURCE=OPUS-MT-models ALL_MODELS=1 register

.PHONY: register-tatoeba-mt
register-tatoeba-mt:
	${MAKE} SOURCE=Tatoeba-MT-models ALL_MODELS=1 register


# include lib/config.mk
include ${REPOHOME}build/config.mk
include ${REPOHOME}build/register.mk
include ${REPOHOME}build/storage.mk




