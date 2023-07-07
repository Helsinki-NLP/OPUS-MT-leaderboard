# -*-makefile-*-
#
# register scores in leaderboards
#  - create files to be added for each language pair and benchmark that we need to update
#  - files have extension 'unsorted.txt' and need to be merged with the leaderbaord file
#  - check top-level makefile about recipes to merge/update
#  - a flag will be created to mark that scores are registered
#

MODEL   ?= Tatoeba-MT-models/deu-eng/opus-2021-02-22

.PHONY: all
all: register-all
	${MAKE} upload-all


include ../build/config.mk
include ../build/register.mk
include ../build/storage.mk




