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


## MODEL_URL: location of the public model (to be stored in the score files)
## MODEL_EVAL_URL: location of the storage space for the evaluation output files
## 	TODO: make sure that this is correct and also saved somewhere!

MODEL_URL       := https://huggingface.co/facebook/${MODEL}
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

all-x2en:
	${MAKE} TRG_LANGS=eng all

all-en2x:
	${MAKE} SRC_LANGS=eng all


PIVOTLANG=eng

pivot:
	${MAKE} TRG_LANGS=${PIVOTLANG} eval-model
	${MAKE} SRC_LANGS=${PIVOTLANG} eval-model

pivot-jobs:
	for m in ${MODELS}; do \
	  ${MAKE} MODEL=$$m pivot.submit; \
	done


include ../lib/config.mk
include ../lib/eval.mk

.PHONY: fetch-model
fetch-model:
	@echo "nothing to be done"

.PHONY: translate
translate: ${WORK_DIR}/${TESTSET}.${LANGPAIR}.output

${WORK_DIR}/%.${LANGPAIR}.output: ${TESTSET_DIR}/%.${SRC} ${TESTSET_DIR}/%.${TRG}
	echo "create $@"
	mkdir -p $(dir $@)
	module load pytorch && \
	cat $< | \
	python3 nllb.py \
		-m ${MODEL} \
		-i $(call NLLB_LANGID_FIXED,$(word 1,$^),${SRC}) \
		-o $(call NLLB_LANGID_FIXED,$(word 2,$^),${TRG}) \
	> $@



