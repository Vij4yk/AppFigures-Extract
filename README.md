# AppFigures-Extract
a wrapper for making requests to AppFigures and returning flattened data structures in PHP

## Summary - The Simplest Way

Uses Basic Authentication to download metrics about your apps. This is the simplest way that I found to interact with the AppFigures API if you just need to download data and do not need OAUTH and do not want to be prompted for a password (i.e CRON).

## Making ETL Easier and Squashing Nested Arrays

AppFigures provides the ability to group by different metrics within your requests. These requests return nested array structures. Assuming that you would like to use this Client for ETL and need a "flattened" list of arrays there are helper functions for squashing these arrays recursively. See 



## Method Chaining

There for your convenience if you are making a other requestes. 
