

## all language pairs and all evaluation metrics

LANGPAIRS  := $(sort $(notdir $(wildcard scores/*-*)))
METRICS    := bleu spbleu chrf chrf++ comet


## default language pair and metric

LANGPAIR   ?= deu-eng
METRIC     ?= $(firstword ${METRICS})


## all score files for the selected metric

METRICFILES = ${sort ${wildcard scores/${LANGPAIR}/*/${METRIC}-scores.txt}}


## UPDATE_SCORE_DIRS   = directory that contains new scores
## UPDATE_LEADERBOARDS = list of leader boards that need to be updated
##    (for all language pairs if UPDATE_ALL_LEADERBOARDS is set)
##    (for the selected LANGPAIR otherwise)

ifdef UPDATE_ALL_LEADERBOARDS
  UPDATE_SCORE_DIRS := $(sort $(dir ${wildcard scores/*/*/*.unsorted.txt}))
else
  UPDATE_SCORE_DIRS := $(sort $(dir ${wildcard scores/${LANGPAIR}/*/*.unsorted.txt}))
endif
UPDATE_LEADERBOARDS := $(foreach m,${METRICS},$(patsubst %,%$(m)-scores.txt,${UPDATE_SCORE_DIRS}))


.PHONY: all
all: released-models.txt release-history.txt
	${MAKE} sort-updated-leaderboards
	${MAKE} all-langpair-scores
	find scores/ -name '*.txt' | xargs git add

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

.PHONY: sort-updated-leaderboards
sort-updated-leaderboards:
	${MAKE} UPDATE_ALL_LEADERBOARDS=1 update-leaderboards



released-models.txt: scores
	find scores/ -name 'bleu-scores.txt' | xargs cat | cut -f2 | sort -u > $@

release-history.txt: released-models.txt
	cat $< | rev | cut -f3 -d'/' | rev > $@.pkg
	cat $< | rev | cut -f2 -d'/' | rev > $@.langpair
	cat $< | rev | cut -f1 -d'/' | rev > $@.model
	cat $< | sed 's/^.*\([0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}\)\.zip$$/\1/' > $@.date
	paste $@.date $@.pkg $@.langpair $@.model | sort -r | sed 's/\.zip$$//' > $@
	rm -f $@.langpair $@.model $@.date $@.pkg

.PHONY: model-list
model-list: scores/${LANGPAIR}/model-list.txt

scores/${LANGPAIR}/model-list.txt: ${METRICFILES}
	find ${dir $@} -name 'bleu-scores.txt' | xargs cut -f2 | sort -u > $@

.PHONY: top-score-file top-scores
top-score-file: scores/${LANGPAIR}/top-${METRIC}-scores.txt
top-scores:
	@for m in ${METRICS}; do \
	  ${MAKE} -s METRIC=$$m top-score-file; \
	done

.PHONY: avg-score-file avg-scores
avg-score-file: scores/${LANGPAIR}/avg-${METRIC}-scores.txt
avg-scores:
	@for m in ${METRICS}; do \
	  ${MAKE} -s METRIC=$$m avg-score-file; \
	done

scores/${LANGPAIR}/top-${METRIC}-scores.txt: ${METRICFILES}
	@rm -f $@
	@for f in $^; do \
	  if [ -s $$f ]; then \
	    t=`echo $$f | cut -f3 -d/`; \
	    echo -n "$$t	" >> $@; \
	    head -1 $$f     >> $@; \
	  fi \
	done

scores/${LANGPAIR}/avg-${METRIC}-scores.txt: ${METRICFILES}
	scripts/average-scores.pl $^ > $@

${UPDATE_LEADERBOARDS}: ${UPDATE_SCORE_DIRS}
	@if [ -e $@ ]; then \
	  if [ $(words $(wildcard ${@:.txt=}*.unsorted.txt)) -gt 0 ]; then \
	    echo "merge and sort ${patsubst scores/%,%,$@}"; \
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
	    echo "merge and sort ${patsubst scores/%,%,$@}"; \
	    cat $(wildcard ${@:.txt=}*.txt) | grep '^[0-9\-]' |\
	    sort -k2,2 -k1,1nr | uniq -f1 | sort -k1,1nr -u > $@.sorted; \
	    rm -f $(wildcard ${@:.txt=}*.txt); \
	    mv $@.sorted $@; \
	  fi; \
	fi

