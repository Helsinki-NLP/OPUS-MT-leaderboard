# -*-makefile-*-
#
#--------------------------------------------------------------------
#  make remove MODEL=modelname
#  make remove BENCHMARK=testset
#  make remove MODEL=modelname BENCHMARK=testset
#
#  make remove BENCHMARK=testset LANGPAIR=langpair
#  make remove MODEL=modelname BENCHMARK=testset LANGPAIR=langpair
#
#  make remove-devsets
#  make cleanup
#--------------------------------------------------------------------


REPOHOME := $(dir $(lastword ${MAKEFILE_LIST}))../
MAKEDIR  := ${REPOHOME}build/

include ${MAKEDIR}env.mk
include ${MAKEDIR}remove.mk
include ${MAKEDIR}misc.mk
include ${MAKEDIR}slurm.mk


reorganize:
	make create-model-zipfiles
	make extract-eval-files
	make delete-eval-files
	make delete-log-files
	find ../models -name '*.txt' -empty -delete
	find ../models -name '*.zip' -empty -delete
	find ../models -name '*.output' -empty -delete
	find ../models -name '*.eval' -empty -delete
	find ../models -name '*.eval' | xargs touch

push-git:
	module load git && find ../models -name '*.eval' | xargs git add
	module load git && git commit -am 'eval files added'
	module load git && git push origin master
	module load git && \
	for s in {a..z}; do \
	  find ../models -name '*$$s.output' | xargs git add; \
	  git commit -am 'output files added (ending in $$s)'; \
	  git push origin master; \
	done
	module load git && find ../models -name '*.zip' | grep -v eval | xargs git add
	module load git && git commit -am 'zip files added'
	module load git && git push origin master

push-git-output:
	find ../models -mindepth 3 -maxdepth 3 -type d -exec make {}.add-outputs-to-git \;

%.add-outputs-to-git:
	module load git && find $(@:.add-outputs-to-git=) -name '*.output' | xargs git add
	module load git && git commit -am 'output files added for $(@:.add-outputs-to-git=)'
	module load git && git push origin master


push-git-zip:
	find ../models -mindepth 2 -maxdepth 2 -type d -exec make {}.add-zips-to-git \;

%.add-zips-to-git:
	module load git && find $(@:.add-zips-to-git=) -name '*.zip' | xargs git add
	module load git && git commit -am 'zip files added for $(notdir $(@:.add-zips-to-git=))'
	module load git && git push origin master


UNTRACKED_ZIP_FILES := $(filter-out %.eval.zip,$(filter %.zip,\
		$(shell module load git && git ls-files --others --exclude-standard ../models)))
PUSH_UNTRACKED_ZIP_FILES := $(patsubst %,%-pushgit,${UNTRACKED_ZIP_FILES})

push-untracked-zip-files: ${PUSH_UNTRACKED_ZIP_FILES}

%-pushgit:
	module load git && git add $(@:-pushgit=)
	module load git && git commit -am 'zip file added'
	module load git && git push origin master
