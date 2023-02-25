# -*-makefile-*-
#
# recipes for runnin benchmarks on NLLB from huggingface
#

PWD      := ${shell pwd}
REPOHOME := ${PWD}/../../


## model variants

MODELS := nllb-200-distilled-600M nllb-200-distilled-1.3B nllb-200-1.3B # nllb-200-3.3B

## all languages that the model supports
## TODO: check that this is all correct

FLORES200_LANGS = ace_Arab ace_Latn acm acq aeb afr ajp aka als amh apc ara ara_Latn ars ary arz asm ast awa ayr azb azj bak bam ban bel bem ben bho bjn_Arab bjn_Latn bod bos bug bul cat ceb ces cjk ckb cmn_Hans cmn_Hant crh cym dan deu dik dyu dzo ell eng epo est eus ewe fao fij fin fon fra fur fuv gaz gla gle glg grn guj hat hau heb hin hne hrv hun hye ibo ilo ind isl ita jav jpn kab kac kam kan kas_Arab kas_Deva kat kaz kbp kea khk khm kik kin kir kmb kmr knc_Arab knc_Latn kon kor lao lij lim lin lit lmo ltg ltz lua lug luo lus lvs mag mai mal mar min_Arab min_Latn mkd mlt mni mos mri msa mya nld nno nob npi nso nus nya oci ory pag pan pap pbt pes plt pol por prs quy ron run rus sag san sat scn shn sin slk slv smo sna snd som sot spa srd srp_Cyrl ssw sun swe swh szl tam taq_Latn taq_Tfng tat tel tgk tgl tha tir tpi tsn tso tuk tum tur twi tzm uig ukr umb urd uzn vec vie war wol xho ydd yor

SRC_LANGS := ${FLORES200_LANGS}
TRG_LANGS := ${FLORES200_LANGS}


## all language pairs that the model supports
## --> basically all combinations of languages in the list of languages

MODEL_LANGPAIRS := ${shell for s in ${SRC_LANGS}; do for t in ${TRG_LANGS}; do echo "$$s-$$t"; done done}



## MODEL_EVAL_URL: location of the storage space for the evaluation output files
## 	TODO: make sure that this is correct and also saved somewhere!

MODEL_STORAGE   := https://object.pouta.csc.fi/External-MT-models
MODEL_EVAL_URL  := ${MODEL_STORAGE}/facebook/${MODEL}.eval.zip




## function to lookup the script (necessary extension to lang-IDs in the NLLB model
## + some fixes that OPUS-MT-testsets changed when importing flores200

NLLB_LANGID = ${shell head -5 ${1} | tr "\n" ' ' | langscript -l ${2} -3 || echo ${2}}
NLLB_LANGID_FIXED = $(shell 	l=$(call NLLB_LANGID,${1},${2}); \
				if [ $$l == ara_Arab ]; then \
				  echo arb_Arab; \
			    	elif [ $$l == ara_Latn ]; then \
				  echo arb_Latn; \
			    	elif [ $$l == cmn_Hans ]; then \
				  echo zho_Hans; \
			    	elif [ $$l == cmn_Hant ]; then \
				  echo zho_Hant; \
			    	elif [ $$l == msa_Latn ]; then \
				  echo zsm_Latn; \
				else \
				  echo $$l; \
				fi )


.PHONY: all
all: eval-models


pivot-jobs:
	for m in ${MODELS}; do \
	  ${MAKE} MODEL=$$m eval-pivot.submit; \
	done

pivot-big:
	${MAKE} MODEL=nllb-200-3.3B eval-pivot


jobs:
	for m in ${MODELS}; do \
	  ${MAKE} MODEL=$$m METRICS="bleu spbleu chrf chrf++" eval-model.submit; \
	done



## M2M100 model for en2x and x2en

M2M100_MODELS := m2m100_418M m2m100_1.2B m2m100-12B-last-ckpt
M2M100_MODEL  ?= ${firstword ${M2M100_MODELS}}
BATCH_SIZE    ?= 16

m2m100-pivot:
	${MAKE} -f Makefile.m2m100 MODELS=${M2M100_MODEL} BATCH_SIZE=${BATCH_SIZE} eval-pivot

m2m100-pivot-jobs:
	${MAKE} M2M100_MODEL=m2m100_418M BATCH_SIZE=16 m2m100-pivot.submit
	${MAKE} M2M100_MODEL=m2m100_1.2B BATCH_SIZE=8 m2m100-pivot.submit
	${MAKE} M2M100_MODEL=m2m100-12B-last-ckpt BATCH_SIZE=4 m2m100-pivot.submit
#	for m in ${M2M100_MODELS}; do \
#	  ${MAKE} M2M100_MODEL=$$m m2m100-pivot.submit; \
#	done






include ../lib/config.mk
include ../lib/eval.mk


## MODEL_URL: location of the public model (to be stored in the score files)
MODEL_URL       := https://huggingface.co/facebook/${MODEL}


##------------------------------------------------------
## fetching model checkpoint if necessary
## (only for the big model)
##------------------------------------------------------

.PHONY: fetch-model
fetch-model:
ifeq (${MODEL},nllb-200-3.3B)
	( module load git git-lfs; \
	  mkdir -p facebook; \
	  cd facebook; \
	  git clone ${MODEL_URL}; \
	  cd ${MODEL}; \
	  git-lfs install; \
	  git pull )
else
	@echo "nothing to be done"
endif



##------------------------------------------------------
## translating a test set
##------------------------------------------------------

.PHONY: translate
translate: ${WORK_DIR}/${TESTSET}.${LANGPAIR}.output

ifeq (${MODEL},nllb-200-3.3B)
  RUNSCRIPT = nllb-accelerate.py -b 16
else
  RUNSCRIPT = nllb.py
endif

${WORK_DIR}/%.${LANGPAIR}.output: ${TESTSET_DIR}/%.${SRC} ${TESTSET_DIR}/%.${TRG}
	echo "create $@"
	mkdir -p $(dir $@)
	module load pytorch && \
	cat $< | \
	python3 ${RUNSCRIPT} \
		-m ${MODEL} \
		-i $(call NLLB_LANGID_FIXED,$(word 1,$^),${SRC}) \
		-o $(call NLLB_LANGID_FIXED,$(word 2,$^),${TRG}) \
	> $@



