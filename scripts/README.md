

# show translation diffs


* https://unix.stackexchange.com/questions/286601/how-to-get-character-level-difference-using-diff-command-in-linux-using-shell

```
git diff --word-diff=color --word-diff-regex=. file1 file2
diff -u file1 file2 |perl /usr/share/doc/git/contrib/diff-highlight/diff-highlight
```

* with colors:

```
sudo apt-get install colordiff
diff -u file1 file2 | colordiff | perl /usr/share/doc/git/contrib/diff-highlight/diff-highlight
```


* convert to html (see https://stackoverflow.com/questions/2013091/coloured-git-diff-to-html):

```
wget "http://www.pixelbeat.org/scripts/ansi2html.sh" -O /tmp/ansi2html.sh
chmod +x /tmp/ansi2html.sh
git diff --color-words --no-index orig.txt edited.txt | \
/tmp/ansi2html.sh > 2beshared.html
```

