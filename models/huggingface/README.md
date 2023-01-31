# Candidate Selection Pipeline

This README describes the procedure to select candidates to be added to the OPUS-MT-leaderboard. The main script is main.py where the following method is implemented.

## Available Translation Models

The first step is to search the hub to find out how many translation models are on Hugging Face. This is done by de select_candidates script. We also want to know how many models are from Helsinki-NLP and how many models have at least two languages in their metadata.

* All models: 1851
* Helsinki models:1439
* Translation models found for candidate selection: 412
* Translation models without any language metadata: 161
* Translation models with only one language: 46
* Translation models with at least two languages in their metadata: 205

## Inference of Language Direction
We keep the remaining models to further select candidates. Since HF does not provide source and target tags, we try to infer the language direction from the models' names.

## Pipeline

Finally, for these remaining models we test if they can be loaded only using the translation pipeline from huggingface as we need to automate the process.

## Future steps
We would like to further improve the selection of candidates to be added to the OPUS-MT-leaderboard by:
* adding adhoc models working with other architectures
* adding models without metadata whose language direction can be inferred from the name


