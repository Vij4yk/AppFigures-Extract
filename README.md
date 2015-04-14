# AppFigures-Extract
a wrapper for making requests to AppFigures and returning flattened data structures in PHP

## Summary - The Simplest Way

Uses Basic Authentication to download metrics about your apps. This is the simplest way that I found to interact with the AppFigures API if you just need to download data and do not need OAUTH and do not want to be prompted for a password (i.e for cron jobs).

## Making ETL Easier 

### Squashing Nested Arrays

AppFigures provides the ability to group by different metrics within your requests. These requests return nested array structures. If you are Extract, Transform, and Loading data from AppFigures to your own database, you may desire  a "flattened" list of arrays. See `nestedArrayToListOfArrays`. 

### Parameterizing on "Group By" Keys

`nestedArrayToListOfArrays` is also useful if you would like to preserve the values of the grouping keys within your nested table. I found some idiosyncracies in the current version of the API where group by keys were not the same as labels. This function helps cleanse and preserve all relevant data.

## Method Chaining

There for your convenience if you are making a other requestes. Example Usage:

    $opts = array(
        'group_by' => 'dates,products',
        'start_date' => '2015-03-01',
        'end_date' => '2015-03-01'
    );
 
    $t = new AppFiguresClient();
    $results = $t->get('/reports/sales', $opts)->to_flat_array();`
`
