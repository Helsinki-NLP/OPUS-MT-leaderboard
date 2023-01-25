# -*-makefile-*-


METRICS := bleu spbleu chrf chrf++ comet

## set the home directory of the repository
## this is to find the included makefiles
## (important to have a trailing '/')

SHELL    := bash
TODAY    := $(shell date +%F)

PWD      ?= ${shell pwd}
REPOHOME ?= ${PWD}/../


## work directory (for the temporary models)

WORK_HOME ?= ${PWD}/work
MODEL     ?= $(firstword ${MODELS})
WORK_DIR  ?= ${WORK_HOME}/${MODEL}


include ${REPOHOME}lib/env.mk
include ${REPOHOME}lib/config.mk
include ${REPOHOME}lib/slurm.mk

GPUJOB_HPC_MEM = 20g


## directory with all test sets (submodule OPUS-MT-testsets)

TESTSET_HOME   := ${REPOHOME}OPUS-MT-testsets/testsets
TESTSET_INDEX  := ${REPOHOME}OPUS-MT-testsets/index.txt


## model directory (for test results)
## model score file and zipfile with evaluation results

MODEL_HOME      ?= ${PWD}
MODEL_DIR       = ${MODEL_HOME}/${MODEL}
MODEL_EVALZIP   = ${MODEL_DIR}.eval.zip

ifeq ($(notdir ${MODEL_HOME}),opus)
  LEADERBOARD_DIR = ${REPOHOME}scores
else ifeq ($(notdir ${MODEL_HOME}),opusTC)
  LEADERBOARD_DIR = ${REPOHOME}scores
else
  LEADERBOARD_DIR = ${REPOHOME}external-scores
endif

## convenient function to reverse a list
reverse = $(if $(wordlist 2,2,$(1)),$(call reverse,$(wordlist 2,$(words $(1)),$(1))) $(firstword $(1)),$(1))



## score files with all evaluation results
##   - combination of BLEU and chrF (MODEL_SCORES)
##   - for a specific metric (MODEL_METRIC_SCORES)
##   - all score files (MODEL_EVAL_SCORES)

MODEL_SCORES        = ${MODEL_DIR}.scores.txt
MODEL_METRIC_SCORES = $(patsubst %,${MODEL_DIR}.%-scores.txt,${METRICS})
MODEL_EVAL_SCORES   = ${MODEL_SCORES} ${MODEL_METRIC_SCORES}



#-------------------------------------------------
# all language pairs that the model supports
# find all test sets that we need to consider
#-------------------------------------------------

ALL_LANGPAIRS := $(notdir ${wildcard ${TESTSET_HOME}/*})
LANGPAIRS     := ${sort $(filter ${ALL_LANGPAIRS},${MODEL_LANGPAIRS})}
LANGPAIR      := ${firstword ${LANGPAIRS}}
LANGPAIRSTR   := ${LANGPAIR}
SRC           := ${firstword ${subst -, ,${LANGPAIR}}}
TRG           := ${lastword ${subst -, ,${LANGPAIR}}}
TESTSET_DIR   := ${TESTSET_HOME}/${LANGPAIR}
TESTSETS      := ${notdir ${basename ${wildcard ${TESTSET_DIR}/*.${SRC}}}}
TESTSET       := ${firstword ${TESTSETS}}

