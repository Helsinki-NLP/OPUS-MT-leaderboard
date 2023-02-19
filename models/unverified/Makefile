# -*-makefile-*-
#
# template for evaluating user-contributed translations
#
# What you need to do is
#
#   * set the model-specific variables MODELS, MODEL_LANGPAIRS, MODEL_URL
#   * place the translations into a temporary work directory
#   * run make with appropriate settings
#
#-----------------------------------------------------------------------------
#
# Example: evaluate a user contributed translation of flores101-dev from English to Danish
#          for a user <username> and model <modelname>
#
#  mkdir -p work/username/modelname
#  cp translation-file.txt work/username/modelname/flores101-dev.eng-dan.output
#  make MODELS=username/modelname \
#       MODEL_LANGPAIRS=eng-dan \
#       MODEL_URL=http://path/to/my/wonderful-model \
#       TESTSETS=flores101-dev \
#       eval-model
#
#-----------------------------------------------------------------------------


METRICS := bleu spbleu chrf chrf++


## specify all models

MODELS := user/modelname1 user/modelname2
MODEL  ?= $(firstword ${MODELS})


## all language pairs that the model supports
## use language IDs that match test sets in the OPUS-MT-testsets repository

MODEL_LANGPAIRS := deu-eng eng-deu fin-swe


## MODEL_URL: location of the public model (to be stored in the score files)
## MODEL_EVAL_URL: location of the storage space for the evaluation output files

MODEL_URL       := https://location-of-my-model-storage/${MODEL}
MODEL_EVAL_URL  := https://location-of-my-model-storage/${MODEL}.eval.zip


## typically 'make all' means eval all models
## add whatever is necessary here

.PHONY: all
all: eval-models


## all kinds of common evaluation recipes and variables
## no changes needed (hopefully)

PWD      := ${shell pwd}
REPOHOME := ${PWD}/../../

include ../lib/config.mk
include ../lib/eval.mk

.PHONY: fetch-model
fetch-model:
	@echo "no model to fetch"
