#!/usr/bin/env perl

use strict;
use File::Basename;

my %translations = ();
my %scores = ();
my %globalscores = ();


foreach my $file (@ARGV){
    open F,"<$file" || die "cannot read from $file";

    my $benchmark = basename($file);
    my $model = basename(dirname($file));

    $benchmark=~s/\.[a-z\_]*\-[a-z\_]*\.compare$//;
    $model=~s/\.eval\.d//;

    next if ($benchmark=~/news.*\-[a-z]{4}/);

    ## comet scores for each segment
    my $cometfile = $file;
    $cometfile=~s/\.compare$/.comet/;
    my $readscores = 0 ;
    $readscores = 1 if (open C,"<$cometfile");

    ## global evaluation scores
    my $evalfile = $file;
    $evalfile=~s/\.compare$/.eval/;
    my ($bleuscore,$chrfscore,$cometscore) = ('unknown','unknown','unknown');
    if (open E,"<$evalfile"){
	my @lines = <E>;
	chomp(@lines);
	if (my @scores = grep(/BLEU/,@lines)){
	    $scores[0]=~s/^\S+\s*\=\s*([0-9\.]+)\s.*$/$1/;
	    $bleuscore = $scores[0];
	}
	if (my @scores = grep(/chrF2/,@lines)){
	    $scores[0]=~s/^\S+\s*\=\s*([0-9\.]+)$/$1/;
	    $chrfscore = $scores[0];
	}
	if (my @scores = grep(/COMET/,@lines)){
	    $scores[0]=~s/^\S+\s*\=\s*([0-9\.]+)$/$1/;
	    $cometscore = $scores[0];
	}
    }
    $globalscores{$benchmark}{$model}{bleu} = $bleuscore;
    $globalscores{$benchmark}{$model}{chrf} = $chrfscore;
    $globalscores{$benchmark}{$model}{comet} = $cometscore;

    while (<F>){
	my $ref = <F>;
	my $sys = <F>;
	<F>;
	$translations{$benchmark}{$_}{reference} = $ref;
	$translations{$benchmark}{$_}{$model} = $sys;
	if ($readscores){
	    my $score = <C>;
	    chomp $score;
	    $score=~s/^.*score:\s*([0-9\-\.]*)$/$1/;
	    $scores{$benchmark}{$_}{$model} = $score;
	}
	else{
	    $scores{$benchmark}{$_}{$model} = 'unknown';
	}
    }
}

foreach my $B (sort keys %translations){
    foreach my $T (keys %{$translations{$B}}){
	print join("\t",($B,'input','','','','',$T));
	print join("\t",($B,'reference','','','','',$translations{$B}{$T}{'reference'}));
	# foreach my $M (sort {$scores{$B}{$T}{$b} <=> $scores{$B}{$T}{$a}} keys %{$translations{$B}{$T}}){
	foreach my $M (sort {$globalscores{$B}{$b}{chrf} <=> $globalscores{$B}{$a}{chrf}} keys %{$translations{$B}{$T}}){
	    next if ($M eq 'reference');
	    print join("\t",($B,$M,$globalscores{$B}{$M}{bleu},$globalscores{$B}{$M}{chrf},$globalscores{$B}{$M}{comet},$scores{$B}{$T}{$M},$translations{$B}{$T}{$M}));
	}
	print "\n";
    }

}
