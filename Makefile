
## directory with leaderboard files depending on the source of the models
##   - default = OPUS-MT models (stored in scores/)
##   - external models (stored in external-scores/)
##   - contributed tanslations (stored in user-scores/)
##   - all scores merged (stored in merged-scores/)

ifeq (${MODELSOURCE},external)
  LEADERBOARD_DIR = external-scores
else ifeq (${MODELSOURCE},contributed)
  LEADERBOARD_DIR = user-scores
else ifeq (${MODELSOURCE},all)
  LEADERBOARD_DIR = merged-scores
else
  LEADERBOARD_DIR = scores
endif


## all language pairs and all evaluation metrics

LANGPAIRS  := $(sort $(notdir $(wildcard ${LEADERBOARD_DIR}/*-*)))
METRICS    := bleu spbleu chrf chrf++ comet


## default language pair and metric

LANGPAIR   ?= deu-eng
METRIC     ?= $(firstword ${METRICS})


## all score files for the selected metric

METRICFILES = ${sort ${wildcard ${LEADERBOARD_DIR}/${LANGPAIR}/*/${METRIC}-scores.txt}}


## UPDATE_SCORE_DIRS   = directory that contains new scores
## UPDATE_LEADERBOARDS = list of leader boards that need to be updated
##    (for all language pairs if UPDATE_ALL_LEADERBOARDS is set)
##    (for the selected LANGPAIR otherwise)

ifdef UPDATE_ALL_LEADERBOARDS
  UPDATE_SCORE_DIRS := $(sort $(dir ${wildcard ${LEADERBOARD_DIR}/*/*/*.unsorted.txt}))
else
  UPDATE_SCORE_DIRS := $(sort $(dir ${wildcard ${LEADERBOARD_DIR}/${LANGPAIR}/*/*.unsorted.txt}))
endif
UPDATE_LEADERBOARDS := $(foreach m,${METRICS},$(patsubst %,%$(m)-scores.txt,${UPDATE_SCORE_DIRS}))


.PHONY: all
all: released-models.txt release-history.txt
	${MAKE} refresh-leaderboards
	${MAKE} all-langpair-scores
	find ${LEADERBOARD_DIR}/ -name '*.txt' | xargs git add


.PHONY: all-external
all-external:
	${MAKE} MODELSOURCE=external update-all-leaderboards
	${MAKE} MODELSOURCE=external all-langpair-scores
	find external-scores/ -name '*.txt' | xargs git add

.PHONY: all-contributed
all-contributed:
	${MAKE} MODELSOURCE=contributed update-all-leaderboards
	${MAKE} MODELSOURCE=contributed all-langpair-scores
	find user-scores/ -name '*.txt' | xargs git add


.PHONY: all-langpair-scores
all-langpair-scores:
	@for l in ${LANGPAIRS}; do \
	  echo "extract top/avg scores for $$l"; \
	  ${MAKE} -s LANGPAIR=$$l top-scores; \
	  ${MAKE} -s LANGPAIR=$$l avg-scores; \
	  ${MAKE} -s LANGPAIR=$$l model-list; \
	done

.PHONY: all-avg-scores
all-avg-scores:
	@for l in ${LANGPAIRS}; do \
	  echo "extract avg scores for $$l"; \
	  ${MAKE} -s LANGPAIR=$$l avg-scores; \
	done

.PHONY: all-top-scores
all-top-scores:
	@for l in ${LANGPAIRS}; do \
	  echo "extract top scores for $$l"; \
	  ${MAKE} -s LANGPAIR=$$l top-scores; \
	done

.PHONY: all-model-lists
all-model-lists:
	@for l in ${LANGPAIRS}; do \
	  echo "extract model lists $$l"; \
	  ${MAKE} -s LANGPAIR=$$l model-list; \
	done



.PHONY: update-leaderboards
update-leaderboards:  ${UPDATE_LEADERBOARDS}

.PHONY: update-all-leaderboards
update-all-leaderboards:
	@for l in ${LANGPAIRS}; do \
	  ${MAKE} -s LANGPAIR=$$l update-leaderboards; \
	done

.PHONY: sort-updated-leaderboards refresh-leaderboards
sort-updated-leaderboards refresh-leaderboards:
	${MAKE} UPDATE_ALL_LEADERBOARDS=1 update-leaderboards



released-models.txt: scores
	find ${LEADERBOARD_DIR}/ -name 'bleu-scores.txt' | xargs cat | cut -f2 | sort -u > $@

release-history.txt: released-models.txt
	cat $< | rev | cut -f3 -d'/' | rev > $@.pkg
	cat $< | rev | cut -f2 -d'/' | rev > $@.langpair
	cat $< | rev | cut -f1 -d'/' | rev > $@.model
	cat $< | sed 's/^.*\([0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}\)\.zip$$/\1/' > $@.date
	paste $@.date $@.pkg $@.langpair $@.model | sort -r | sed 's/\.zip$$//' > $@
	rm -f $@.langpair $@.model $@.date $@.pkg

.PHONY: model-list
model-list: ${LEADERBOARD_DIR}/${LANGPAIR}/model-list.txt

${LEADERBOARD_DIR}/${LANGPAIR}/model-list.txt: ${METRICFILES}
	find ${dir $@} -name 'bleu-scores.txt' | xargs cut -f2 | sort -u > $@

.PHONY: top-score-file top-scores
top-score-file: ${LEADERBOARD_DIR}/${LANGPAIR}/top-${METRIC}-scores.txt
top-scores:
	@for m in ${METRICS}; do \
	  ${MAKE} -s METRIC=$$m top-score-file; \
	done

.PHONY: avg-score-file avg-scores
avg-score-file: ${LEADERBOARD_DIR}/${LANGPAIR}/avg-${METRIC}-scores.txt
avg-scores:
	@for m in ${METRICS}; do \
	  ${MAKE} -s METRIC=$$m avg-score-file; \
	done

${LEADERBOARD_DIR}/${LANGPAIR}/top-${METRIC}-scores.txt: ${METRICFILES}
	@rm -f $@
	@for f in $^; do \
	  if [ -s $$f ]; then \
	    t=`echo $$f | cut -f3 -d/`; \
	    echo -n "$$t	" >> $@; \
	    head -1 $$f     >> $@; \
	  fi \
	done

${LEADERBOARD_DIR}/${LANGPAIR}/avg-${METRIC}-scores.txt: ${METRICFILES}
	tools/average-scores.pl $^ > $@

${UPDATE_LEADERBOARDS}: ${UPDATE_SCORE_DIRS}
	@if [ -e $@ ]; then \
	  if [ $(words $(wildcard ${@:.txt=}*.unsorted.txt)) -gt 0 ]; then \
	    echo "merge and sort ${patsubst ${LEADERBOARD_DIR}/%,%,$@}"; \
	    sort -k2,2 -k1,1nr $@                           > $@.old.txt; \
	    cat $(wildcard ${@:.txt=}*.unsorted.txt) | \
	    grep '^[0-9\-]' | sort -k2,2 -k1,1nr            > $@.new.txt; \
	    sort -m $@.new.txt $@.old.txt |\
	    uniq -f1 | sort -k1,1nr -u                      > $@.sorted; \
	    rm -f $@.old.txt $@.new.txt; \
	    rm -f $(wildcard ${@:.txt=}*.unsorted.txt); \
	    mv $@.sorted $@; \
	  fi; \
	else \
	  if [ $(words $(wildcard ${@:.txt=}*.txt)) -gt 0 ]; then \
	    echo "merge and sort ${patsubst ${LEADERBOARD_DIR}/%,%,$@}"; \
	    cat $(wildcard ${@:.txt=}*.txt) | grep '^[0-9\-]' |\
	    sort -k2,2 -k1,1nr | uniq -f1 | sort -k1,1nr -u > $@.sorted; \
	    rm -f $(wildcard ${@:.txt=}*.txt); \
	    mv $@.sorted $@; \
	  fi; \
	fi



## merge external and internal scores
## TODO: do we really want to create a copy of all files?

MERGED_METRICFILES = $(patsubst ${LEADERBOARD_DIR}/%,merged-scores/%,${METRICFILES})

.PHONY: merge-with-external
merge-with-external: ${MERGED_METRICFILES}
${MERGED_METRICFILES}: merged-scores/%: ${LEADERBOARD_DIR}/%
	mkdir (dir $@)
	-cat $< $(patsubst merged-scores/%,external-scores/%,$@) |\
	sort -k1,1nr -u | uniq -f2 > $@

.PHONY: merge-all-scores
 merge-all-scores:
	@for l in ${LANGPAIRS}; do \
	  ${MAKE} -s LANGPAIR=$$l merge-with-external; \
	done

