

include Makefile.def


NLLB_MODELS := nllb-200-distilled-600M nllb-200-distilled-1.3B nllb-200-1.3B # nllb-200-3.3B
NLLB_MODEL  := $(firstword ${NLLB_MODELS})



MODEL_STORAGE   := https://object.pouta.csc.fi/External-MT-models
MODEL_LANGPAIR  := mul-mul
MODEL           := ${MODEL_LANGPAIR}/${NLLB_MODEL}
MODEL_URL       := https://huggingface.co/facebook/${NLLB_MODEL}
MODEL_EVAL_URL  := ${MODEL_STORAGE}/${NLLB_MODEL}.eval.zip




FLORES200_LANGS = ace_Arab ace_Latn acm acq aeb afr ajp aka als amh apc ara ara_Latn ars ary arz asm ast awa ayr azb azj bak bam ban bel bem ben bho bjn_Arab bjn_Latn bod bos bug bul cat ceb ces cjk ckb cmn_Hans cmn_Hant crh cym dan deu dik dyu dzo ell eng epo est eus ewe fao fij fin fon fra fur fuv gaz gla gle glg grn guj hat hau heb hin hne hrv hun hye ibo ilo ind isl ita jav jpn kab kac kam kan kas_Arab kas_Deva kat kaz kbp kea khk khm kik kin kir kmb kmr knc_Arab knc_Latn kon kor lao lij lim lin lit lmo ltg ltz lua lug luo lus lvs mag mai mal mar min_Arab min_Latn mkd mlt mni mos mri msa mya nld nno nob npi nso nus nya oci ory pag pan pap pbt pes plt pol por prs quy ron run rus sag san sat scn shn sin slk slv smo sna snd som sot spa srd srp_Cyrl ssw sun swe swh szl tam taq_Latn taq_Tfng tat tel tgk tgl tha tir tpi tsn tso tuk tum tur twi tzm uig ukr umb urd uzn vec vie war wol xho ydd yor

# SRCLANGS = fin deu swe
# TRGLANGS = eng fin

SRCLANGS = ${FLORES200_LANGS}
TRGLANGS = ${FLORES200_LANGS}


MODEL_LANGPAIRS := ${shell for s in ${SRCLANGS}; do for t in ${TRGLANGS}; do echo "$$s-$$t"; done done}


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



NLLB_LANGID = ${shell head -5 ${1} | tr "\n" ' ' | langscript -l ${2} -3 || echo ${2}}


.PHONY: all
all: eval-nllb-models

all-x2en:
	${MAKE} TRGLANGS=eng all

all-en2x:
	${MAKE} SRCLANGS=eng all


.PHONY: eval-nllb-models
eval-nllb-models:
	for m in ${NLLB_MODELS}; do \
	  ${MAKE} NLLB_MODEL=$$m eval-langpairs; \
	done

## fetch only the model
.PHONY: fetch-model
fetch-model:
	@echo "nothing to be done"

eval-nllb: 	${MODEL_DIR}/${TESTSET}.${LANGPAIR}.compare \
		${MODEL_DIR}/${TESTSET}.${LANGPAIR}.eval


# ${LANGPAIR}/%.${NLLB_MODEL}.${TRG}: ${TESTSET_DIR}/%.${SRC} ${TESTSET_DIR}/%.${TRG}
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

# ${LANGPAIR}/%.${NLLB_MODEL}.${TRG}.spbleu: ${LANGPAIR}/%.${NLLB_MODEL}.${TRG} ${TESTSET_DIR}/%.${TRG}
# 	cat $< | sacrebleu -f text --metrics=bleu --tokenize flores200 $(word 2,$^) > $@

# ${WORK_DIR}/%.${LANGPAIR}.output: ${LANGPAIR}/%.${NLLB_MODEL}.${TRG}
# 	mkdir -p $(dir $@)
# 	cp $< $@





##################################################
##
## move to common makefile
##
##################################################

EVAL_LANGPAIR_TARGET = $(patsubst %,%-eval,${LANGPAIRS})

.PHONY: eval-langpairs
eval-langpairs: ${EVAL_LANGPAIR_TARGET}
#	for l in ${LANGPAIRS}; do \
#	  ${MAKE} LANGPAIR=$$l eval-testsets; \
#	done

.PHONY: ${EVAL_LANGPAIR_TARGET}
${EVAL_LANGPAIR_TARGET}:
	${MAKE} LANGPAIR=$(@:-eval=) eval-testsets


TRANSLATED_BENCHMARKS = $(patsubst %,${MODEL_DIR}/%.${LANGPAIR}.compare,${TESTSETS})
EVALUATED_BENCHMARKS  = $(patsubst %,${MODEL_DIR}/%.${LANGPAIR}.eval,${TESTSETS})
BENCHMARK_SCORE_FILES = $(foreach m,${METRICS},$(patsubst %.eval,%.${m},${EVALUATED_BENCHMARKS}))

## don't delete those files when used in implicit rules
.NOTINTERMEDIATE: ${TRANSLATED_BENCHMARKS} ${EVALUATED_BENCHMARKS} ${BENCHMARK_SCORE_FILES}


.PHONY: eval-testsets
eval-testsets: ${TRANSLATED_BENCHMARKS} ${EVALUATED_BENCHMARKS}


##-------------------------------------------------
## evaluate the model with all benchmarks available
## register the scores and update the leaderboard
## pack evaluation files in a zip file
##-------------------------------------------------

.PHONY: eval-model
eval-model: ${MODEL_EVAL_SCORES}
	${MAKE} model-scores
	${MAKE} pack-model-scores
#	if [ -e $< ]; then \
#	  ${MAKE} -f Makefile.register register-scores; \
#	fi



##-------------------------------------------------
## evaluate the model with all benchmarks available
## - if a model score file is missing:
##      * fetch model and evaluation files
##      * try to run evaluation again
##      * make model score files again
##-------------------------------------------------

.PHONY: scores model-scores
scores model-scores: ${MODEL_EVAL_SCORES}
	@if [ $(words $(wildcard $^)) -ne $(words $^) ]; then \
	  echo "score files missing ... fetch model and scores!"; \
	  ${MAKE} fetch; \
	  ${MAKE} eval-langpairs; \
	  ${MAKE} cleanup; \
	  ${MAKE} ${MODEL_EVAL_SCORES}; \
	fi







${MODEL_DIR}/%.${LANGPAIR}.compare: 	${TESTSET_DIR}/%.${SRC} \
					${TESTSET_DIR}/%.${TRG} \
					${WORK_DIR}/%.${LANGPAIR}.output
	@mkdir -p ${dir $@}
	if [ -s $(word 3,$^) ]; then \
	  paste -d "\n" $^ | sed 'n;n;G;' > $@; \
	fi


## concatenate all scores into one file
## exception: comet scores: take only the last line and add the name of the metric
## all others: just add the while file content (assume sacrebleu output)

# ${MODEL_DIR}/%.${LANGPAIR}.eval: ${MODEL_DIR}/%.${LANGPAIR}.compare

${EVALUATED_BENCHMARKS}: ${BENCHMARK_SCORE_FILES}
	${MAKE} $(patsubst %,$(basename $@).%,${METRICS})
	for m in ${METRICS}; do \
	  if [ $$m == comet ]; then \
	    tail -1 $(basename $@).$$m | sed 's/^.*score:/COMET+default =/' >> $@; \
	  else \
	    cat $(basename $@).$$m >> $@; \
	  fi \
	done
	rev $@ | sort | uniq -f2 | rev > $@.uniq
	mv -f $@.uniq $@


## adjust tokenisation to non-space-separated languages
ifneq ($(filter cmn yue zho,${TRG}),)
  SACREBLEU_PARAMS = --tokenize zh
endif

ifneq ($(filter jpn,${TRG}),)
  SACREBLEU_PARAMS = --tokenize ja-mecab
endif

ifneq ($(filter kor,${TRG}),)
  SACREBLEU_PARAMS = --tokenize ko-mecab
endif

${MODEL_DIR}/%.${LANGPAIR}.spbleu: ${MODEL_DIR}/%.${LANGPAIR}.compare
	mkdir -p ${dir $@}
	sed -n '1~4p' $< > $@.src
	sed -n '2~4p' $< > $@.ref
	sed -n '3~4p' $< > $@.hyp
	cat $@.hyp | \
	sacrebleu -f text --metrics=bleu --tokenize flores200 $@.ref > $@
	rm -f $@.src $@.ref $@.hyp

${MODEL_DIR}/%.${LANGPAIR}.bleu: ${MODEL_DIR}/%.${LANGPAIR}.compare
	mkdir -p ${dir $@}
	sed -n '1~4p' $< > $@.src
	sed -n '2~4p' $< > $@.ref
	sed -n '3~4p' $< > $@.hyp
	cat $@.hyp | \
	sacrebleu -f text ${SACREBLEU_PARAMS} $@.ref > $@
	rm -f $@.src $@.ref $@.hyp

${MODEL_DIR}/%.${LANGPAIR}.chrf: ${MODEL_DIR}/%.${LANGPAIR}.compare
	mkdir -p ${dir $@}
	sed -n '1~4p' $< > $@.src
	sed -n '2~4p' $< > $@.ref
	sed -n '3~4p' $< > $@.hyp
	cat $@.hyp | \
	sacrebleu -f text ${SACREBLEU_PARAMS} --metrics=chrf --width=3 $@.ref |\
	perl -pe 'unless (/version\:1\./){@a=split(/\s+/);$$a[-1]/=100;$$_=join(" ",@a)."\n";}' > $@
	rm -f $@.src $@.ref $@.hyp

${MODEL_DIR}/%.${LANGPAIR}.chrf++: ${MODEL_DIR}/%.${LANGPAIR}.compare
	mkdir -p ${dir $@}
	sed -n '1~4p' $< > $@.src
	sed -n '2~4p' $< > $@.ref
	sed -n '3~4p' $< > $@.hyp
	cat $@.hyp | \
	sacrebleu -f text ${SACREBLEU_PARAMS} --metrics=chrf --width=3 --chrf-word-order 2 $@.ref |\
	perl -pe 'unless (/version\:1\./){@a=split(/\s+/);$$a[-1]/=100;$$_=join(" ",@a)."\n";}' > $@
	rm -f $@.src $@.ref $@.hyp

${MODEL_DIR}/%.${LANGPAIR}.ter: ${MODEL_DIR}/%.${LANGPAIR}.compare
	mkdir -p ${dir $@}
	sed -n '1~4p' $< > $@.src
	sed -n '2~4p' $< > $@.ref
	sed -n '3~4p' $< > $@.hyp
	cat $@.hyp | \
	sacrebleu -f text ${SACREBLEU_PARAMS} --metrics=ter $@.ref > $@
	rm -f $@.src $@.ref $@.hyp

ifneq (${GPU_AVAILABLE},1)
  COMET_PARAM += --gpus 0
endif

${MODEL_DIR}/%.${LANGPAIR}.comet: ${MODEL_DIR}/%.${LANGPAIR}.compare
	mkdir -p ${dir $@}
	sed -n '1~4p' $< > $@.src
	sed -n '2~4p' $< > $@.ref
	sed -n '3~4p' $< > $@.hyp
	${LOAD_COMET_ENV} ${COMET_SCORE} ${COMET_PARAM} \
		-s $@.src -r $@.ref -t $@.hyp | cut -f2,3 > $@
	rm -f $@.src $@.ref $@.hyp



#-------------------------------------------------
# collect BLEU and chrF scores in one file
#-------------------------------------------------
#
# updating scores for models that already have some scores registered
# - need to fetch eval file package
# - avoid re-running things that are already done
# - ingest the new evaluation scores
#

${MODEL_SCORES}: ${TESTSET_INDEX}
	-if [ ! -e $@ ]; then \
	  mkdir -p $(dir $@); \
	  wget -qq -O $@ ${MODEL_STORAGE}/${MODEL}.scores.txt; \
	fi
	${MAKE} fetch
	${MAKE} eval-langpairs
	${MAKE} cleanup
	if [ -d ${MODEL_DIR} ]; then \
	  grep -H BLEU ${MODEL_DIR}/*.bleu | sort                  > $@.bleu; \
	  grep -H chrF ${MODEL_DIR}/*.chrf | sort                  > $@.chrf; \
	  cut -f1 -d: $@.bleu | rev | cut -f2 -d. | rev            > $@.langs; \
	  cut -f1 -d: $@.bleu | rev | cut -f1 -d/ | cut -f3- -d. | rev  > $@.testsets; \
	  cat $@.chrf | rev | cut -f1 -d' ' | rev                  > $@.chrf-scores; \
	  cut -f2 -d= $@.bleu | cut -f2 -d' '                      > $@.bleu-scores; \
	  cut -f1 -d: $@.bleu | sed 's#^.*$$#${MODEL_URL}#'        > $@.urls; \
	  cut -f1 -d: $@.bleu | sed 's/.bleu$$/.compare/' | \
	  xargs wc -l |  grep -v '[0-9] total' | \
	  perl -pe '$$_/=4;print "\n"' | tail -n +2                > $@.nrlines; \
	  cat $@.bleu | rev | cut -f1 -d' ' | rev | cut -f1 -d')'  > $@.nrwords; \
	  if [ -e $@ ]; then mv $@ $@.old; fi; \
	  paste $@.langs $@.testsets \
		$@.chrf-scores $@.bleu-scores \
		$@.urls $@.nrlines $@.nrwords |\
	  sed -e 's/\(news.*[0-9][0-9][0-9][0-9]\)-[a-z][a-z][a-z][a-z]	/\1	/' |  \
	  sed -e 's/\(news.*2021\)\.[a-z][a-z]\-[a-z][a-z]	/\1	/' |\
	  sort -k1,1 -k2,2 -k4,4nr -k6,6nr -k7,7nr | \
	  rev | uniq -f5 | rev | sort -u                           > $@; \
	  if [ -e $@.old ]; then \
	    mv $@ $@.new; \
	    sort -k1,1 -k2,2 -m $@.new $@.old | \
	    rev | uniq -f5 | rev | sort -u                         > $@; \
	  fi; \
	  rm -f $@.bleu $@.chrf $@.langs $@.testsets \
		$@.chrf-scores $@.bleu-scores \
		$@.urls $@.nrlines $@.nrwords $@.old $@.new; \
	fi


##-------------------------------------------------
## generic recipe for extracting scores for a metric
## (works for all sacrebleu results but not for other metrics)
##-------------------------------------------------

${MODEL_DIR}.%-scores.txt: ${MODEL_SCORES}
	if [ -d ${MODEL_DIR} ]; then \
	  mkdir -p $(dir $@); \
	  grep -H . ${MODEL_DIR}/*.$(patsubst ${MODEL_DIR}.%-scores.txt,%,$@) > $@.all; \
	  cut -f1 -d: $@.all | rev | cut -f2 -d. | rev                        > $@.langs; \
	  cut -f1 -d: $@.all | rev | cut -f1 -d/ | cut -f3- -d. | rev         > $@.testsets; \
	  cut -f3 -d ' '  $@.all                                              > $@.scores; \
	  paste $@.langs $@.testsets $@.scores                               >> $@; \
	  cat $@ |\
	  sed -e 's/\(news.*[0-9][0-9][0-9][0-9]\)-[a-z][a-z][a-z][a-z]	/\1	/' |  \
	  sed -e 's/\(news.*2021\)\.[a-z][a-z]\-[a-z][a-z]	/\1	/' |\
	  rev | uniq -f1 | rev                                                > $@.sorted; \
	  mv -f $@.sorted $@; \
	  rm -f $@.all $@.langs $@.testsets $@.scores; \
	fi


## specific recipe for COMET scores

${MODEL_DIR}.comet-scores.txt: ${MODEL_SCORES}
	if [ -d ${MODEL_DIR} ]; then \
	  mkdir -p $(dir $@); \
	  grep -H '^score:' ${MODEL_DIR}/*.comet | sort                  > $@.comet; \
	  cut -f1 -d: $@.comet | rev | cut -f2 -d. | rev                 > $@.langs; \
	  cut -f1 -d: $@.comet | rev | cut -f1 -d/ | cut -f3- -d. | rev  > $@.testsets; \
	  cat $@.comet | rev | cut -f1 -d' ' | rev                       > $@.comet-scores; \
	  paste $@.langs $@.testsets $@.comet-scores                     >> $@; \
	  cat $@ |\
	  sed -e 's/\(news.*[0-9][0-9][0-9][0-9]\)-[a-z][a-z][a-z][a-z]	/\1	/' |  \
	  sed -e 's/\(news.*2021\)\.[a-z][a-z]\-[a-z][a-z]	/\1	/' |\
	  rev | uniq -f1 | rev                                           > $@.sorted; \
	  mv -f $@.sorted $@; \
	  rm -f $@.comet $@.langs $@.testsets $@.comet-scores; \
	fi





## prepare translation model and fetch existing evaluation files
.PHONY: fetch
fetch: fetch-model fetch-model-scores

## fetch existing evaluation files
.PHONY: fetch-model-scores
fetch-model-scores: ${MODEL_DIR}


## prepare the model evaluation file directory
## fetch already existing evaluations
${MODEL_DIR}:
	mkdir -p $@
	-if [ -e ${MODEL_EVALZIP} ]; then \
	  cd ${MODEL_DIR}; \
	  unzip -n ${MODEL_EVALZIP}; \
	fi
	-${WGET} -q -O ${MODEL_DIR}/eval.zip ${MODEL_EVAL_URL}
	-if [ -e ${MODEL_DIR}/eval.zip ]; then \
	  cd ${MODEL_DIR}; \
	  unzip -n eval.zip; \
	  rm -f eval.zip; \
	fi

.PHONY: pack-model-scores
pack-model-scores:
	if [ -d ${MODEL_DIR} ]; then \
	  cd ${MODEL_DIR} && find . -name '*.*' | xargs zip ${MODEL_EVALZIP}; \
	  find ${MODEL_DIR} -name '*.*' -delete; \
	  rmdir ${MODEL_DIR}; \
	fi

.PHONY: cleanup
cleanup:
ifneq (${WORK_DIR},)
ifneq (${WORK_DIR},/)
ifneq (${WORK_DIR},.)
ifneq (${WORK_DIR},..)
	rm -fr ${WORK_DIR}
	-rmdir ${WORK_HOME}/${MODEL_LANGPAIR}
endif
endif
endif
endif
