
# Model Evaluation

The repository comes with recipes for evaluating MT models. For example, the `models/opusTC` sub directory includes makefile recipes for systematically testing released OPUS-MT models that use the Tatoeba translation challenge data (more details further down).




## Adding model evaluation recipes

You can add new recipes for additional model types by creating a new sub directory in this folder and implementing the scripts that are necessary to create all necessary files for registering the benchmark results.

When evaluating a model you need to create or update all relevant model score files in the same format as specified above. Additionally, you should place the new results in the language score directory to be registered in the leaderboards of individual benchmarks.

In order to avoid any conflicts the procedure is to first produce temporary files that list new scores to be registered and sorted into the benchmark leaderboards. In order to use the existing recipes for updating and sorting those files you need to place the scores in files that use the following naming conventions:

```
scores/src-trg/benchmark/metric-scores.modelname.unsorted.txt
```

The format follows the same TAB-separated plain text format with 2 columns as we use for general benchmark leaderboards. `modelname` can refer to any string that gives the file a unique name to allow several new scores to be registered for the same benchmark and language pair.

There is a [Makefile template](Makefile.eval-model-template) that you can use to implement your own model evaluation recipes. Simply create a new sub-directory for the model type you want to support and adapt the recipes to work with your translation tool. The main changes that need to be done are:

* setting model-specific variables (names and supported language pairs etc)
* implementing a recipe to fetch and setup the model and translation tool
* implement the recipe for translating a text with your model

Other recipes for running through benchmarks in our collection and registering the scores in our leaderboard files are inmplemented in the shared makefiles in [lib/](lib). Once all model score files have been created you can then run

```
make MODEL_HOME=my-new-modeldir all
```

to register scores for the inclusion in leaderboards. Make sure that all score files in `my-new-modeldir/*-scores.txt` look correct and correspond to the formats we need. The temporary files for merging scores into leaderboards as mentioned above will then be created from that recipe. How to merge them with existing leaderboards is explained in the section below.



## Updating leaderboards

The top-level makefile implements recipes that can be used to update and sort leaderboards for wich unsorted new scores are listed in the repository. It is possible to update only the tables for a selected language pair, to run through all language pairs or update all leaderboards for which unsorted files are found (`refresh-leaderboards`).

```
make LANGPAIR=deu-ukr update-leaderboards   # update leaderboards for deu-ukr
make update-all-leaderboards                # run through all language pairs and update
make refresh-leaderboards                   # refresh all leaderboards that require updates
```


Top-score files and average score files can also be updated using the high-level makefile recipes. Similar to above, specific language pairs can be selected or all language pairs can be updated.

```
make LANGPAIR=deu-ukr top-scores
make LANGPAIR=deu-ukr avg-scores

make all-top-scores
make all-avg-scores
```


## Evaluation recipes for OPUS-MT models (opus)


## Evaluation recipes for Tatoeba Translation Challenge models (opusTC)

Recipes for opusTC model evaluation are implemented in makefiles. Make sure to change to the `opusTC` directory (`cd models/opusTC`). Run all necessary evaluations for all released opusTC models (or run in reverse order):

```
make all
make all-reverse
```

Submit SLURM jobs for the same targets:

```
make all.submit
make all-reverse.submit
```


Find models for which some evaluation is missing and then run the evaluation for those.

```
make find-missing
make all.submit
```
