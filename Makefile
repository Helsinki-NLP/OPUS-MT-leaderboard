

LANGPAIRS = ${notdir $(wildcard scores/*-*)}
LANGPAIR  = deu-eng
BLEUFILES = ${sort ${wildcard scores/${LANGPAIR}/*/bleu-scores.txt}}
CHRFFILES = ${sort ${wildcard scores/${LANGPAIR}/*/chrf-scores.txt}}
COMETFILES = ${sort ${wildcard scores/${LANGPAIR}/*/comet-scores.txt}}

all: released-models.txt release-history.txt
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

scores/${LANGPAIR}/model-list.txt: ${BLEUFILES}
	find ${dir $@} -name 'bleu-scores.txt' | xargs cut -f2 | sort -u > $@

.PHONY: top-scores
top-scores: scores/${LANGPAIR}/top-bleu-scores.txt scores/${LANGPAIR}/top-chrf-scores.txt scores/${LANGPAIR}/top-comet-scores.txt

.PHONY: avg-scores
avg-scores: scores/${LANGPAIR}/avg-bleu-scores.txt scores/${LANGPAIR}/avg-chrf-scores.txt scores/${LANGPAIR}/avg-comet-scores.txt


scores/${LANGPAIR}/top-bleu-scores.txt: ${BLEUFILES}
	@rm -f $@
	@for f in $^; do \
	  if [ -s $$f ]; then \
	    t=`echo $$f | cut -f3 -d/`; \
	    echo -n "$$t	" >> $@; \
	    head -1 $$f     >> $@; \
	  fi \
	done

scores/${LANGPAIR}/top-chrf-scores.txt: ${CHRFFILES}
	@rm -f $@
	@for f in $^; do \
	  if [ -s $$f ]; then \
	    t=`echo $$f | cut -f3 -d/`; \
	    echo -n "$$t	" >> $@; \
	    head -1 $$f     >> $@; \
	  fi \
	done

scores/${LANGPAIR}/top-comet-scores.txt: ${COMETFILES}
	@rm -f $@
	@for f in $^; do \
	  if [ -s $$f ]; then \
	    t=`echo $$f | cut -f3 -d/`; \
	    echo -n "$$t	" >> $@; \
	    head -1 $$f     >> $@; \
	  fi \
	done

scores/${LANGPAIR}/avg-bleu-scores.txt: ${BLEUFILES}
	scripts/average-scores.pl $^ > $@

scores/${LANGPAIR}/avg-chrf-scores.txt: ${CHRFFILES}
	scripts/average-scores.pl $^ > $@

scores/${LANGPAIR}/avg-comet-scores.txt: ${COMETFILES}
	scripts/average-scores.pl $^ > $@
