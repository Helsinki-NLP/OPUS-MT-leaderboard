# -*-makefile-*-
#
# recipes for runnin benchmarks on NLLB from huggingface
#

PWD      := ${shell pwd}
REPOHOME := ${PWD}/../../


## model variants

NLLB_MODELS := nllb-200-distilled-600M nllb-200-distilled-1.3B nllb-200-1.3B # nllb-200-3.3B
NLLB_MODEL  := $(firstword ${NLLB_MODELS})

MODELS := $(patsubst %,unspecified/%,${NLLB_MODELS})
MODEL  ?= $(firstword ${MODELS})


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

MODEL_URL       := https://huggingface.co/facebook/${NLLB_MODEL}
MODEL_STORAGE   := https://object.pouta.csc.fi/External-MT-models
MODEL_EVAL_URL  := ${MODEL_STORAGE}/${NLLB_MODEL}.eval.zip




## function to lookup the script (necessary extension to lamg-IDs in the NLLB model

NLLB_LANGID = ${shell head -5 ${1} | tr "\n" ' ' | langscript -l ${2} -3 || echo ${2}}





.PHONY: all
all:
	for m in ${NLLB_MODELS}; do \
	  ${MAKE} NLLB_MODEL=$$m eval-model; \
	done

all-x2en:
	${MAKE} TRG_LANGS=eng all

all-en2x:
	${MAKE} SRC_LANGS=eng all

all-fin:
	${MAKE} SRC_LANGS=fin NLLB_MODELS=nllb-200-distilled-600M all.submit
	${MAKE} TRG_LANGS=fin NLLB_MODELS=nllb-200-distilled-600M all.submit
	${MAKE} SRC_LANGS=fin NLLB_MODELS=nllb-200-distilled-1.3B all.submit
	${MAKE} TRG_LANGS=fin NLLB_MODELS=nllb-200-distilled-1.3B all.submit
	${MAKE} SRC_LANGS=fin NLLB_MODELS=nllb-200-1.3B all.submit
	${MAKE} TRG_LANGS=fin NLLB_MODELS=nllb-200-1.3B all.submit



include ../lib/config.mk
include ../lib/eval.mk


.PHONY: fetch-model
fetch-model:
	@echo "nothing to be done"

.PHONY: translate
translate: ${WORK_DIR}/%.${LANGPAIR}.output

${WORK_DIR}/%.${LANGPAIR}.output: ${TESTSET_DIR}/%.${SRC} ${TESTSET_DIR}/%.${TRG}
	echo "create $@"
	mkdir -p $(dir $@)
	module load pytorch && \
	cat $< | \
	python3 nllb.py \
		-m ${NLLB_MODEL} \
		-i $(call NLLB_LANGID,$(word 1,$^),${SRC}) \
		-o $(call NLLB_LANGID,$(word 2,$^),${TRG}) \
	> $@



