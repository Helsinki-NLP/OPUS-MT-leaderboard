


## get all benchmarks with supported language pairs from the BLEU score database

benchmarks.txt:
	echo 'select distinct testset,langpair from scores;' | sqlite3 bleu_scores.db | sort \
	| ../build/tools/merge-benchmark-langpairs.pl > $@


## get all supported language pairs from the BLEU score database

langpairs.txt:
	echo 'select distinct langpair from scores;' | sqlite3 bleu_scores.db  | sort > $@




### OBSOLETE THINGS TO BE DELETED ##########


## fix sub-directories for newstests:
## remove langid extension that causes duplicates
## --> still need to sort leaderboards again!

NEWS = ${wildcard *-*/news*-[a-z][a-z][a-z][a-z]}

move:
	for o in ${NEWS}; do \
	  n=`echo $$o | sed 's/\-[a-z][a-z][a-z][a-z]$$//'`; \
	  b=`basename $$o`; \
	  mkdir -p $$n; \
	  mv $$o/bleu-scores.txt $$n/bleu-scores.$$b.unsorted.txt; \
	  mv $$o/chrf-scores.txt $$n/chrf-scores.$$b.unsorted.txt; \
	  rmdir $$o; \
	done

LANGPAIRS = $(sort ${wildcard *-*})
BENCHMARKS = $(sort $(notdir ${wildcard *-*/*}))

langpairs-old.txt:
	@echo "${LANGPAIRS}" | tr ' ' "\n" > $@

benchmarks-old.txt:
	for b in ${BENCHMARKS}; do \
	  echo -n "$$b	" >> $@; \
	  find . -name "$$b" -type d | cut -f2 -d/ | sort -u | tr "\n" ' ' >> $@; \
	  echo "" >> $@; \
	done
#	@echo "${BENCHMARKS}" > $@

