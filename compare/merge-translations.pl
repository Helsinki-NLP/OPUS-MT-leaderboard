#!/usr/bin/env perl

use File::Basename;

my %translations = ();
my %scores = ();

foreach my $file (@ARGV){
    open F,"<$file" || die "cannot read from $file";

    my $cometfile = $file;
    $cometfile=~s/\.compare$/.comet/;
    $readscores = 0 ;
    $readscores = 1 if (open C,"<$cometfile");

    my $benchmark = basename($file);
    my $model = basename(dirname($file));

    $benchmark=~s/\.[a-z\_]*\-[a-z\_]*\.compare$//;
    $model=~s/\.eval\.d//;

    next if ($benchmark=~/news.*\-[a-z]{4}/);

    while (<F>){
	my $ref = <F>;
	my $sys = <F>;
	<F>;
	$translations{$benchmark}{$_}{reference} = $ref;
	$translations{$benchmark}{$_}{$model} = $sys;
	if ($readscores){
	    $score = <C>;
	    chomp $score;
	    $score=~s/^.*score:\s*([0-9\-\.]*)$/$1/;
	    $scores{$benchmark}{$_}{$model} = $score;
	}
	else{
	    $scores{$benchmark}{$_}{$model} = unknown;
	}
    }
}

foreach my $B (sort keys %translations){
    foreach my $T (keys %{$translations{$B}}){
	print join("\t",($B,'input','',$T));
	print join("\t",($B,'reference','',$translations{$B}{$T}{'reference'}));
	foreach my $M (sort {$scores{$B}{$T}{$b} <=> $scores{$B}{$T}{$a}} keys %{$translations{$B}{$T}}){
	    next if ($M eq 'reference');
	    print join("\t",($B,$M,$scores{$B}{$T}{$M},$translations{$B}{$T}{$M}));
	}
	print "\n";
    }

}
