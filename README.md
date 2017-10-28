
## MultiProcess catalog images resizer

### Installation

`composer require phlpdtrt/multiprocess-catalog-images-resizer`


### Configuration

there is nothing to configure

### Usage

simply call the console command with the desired amount of workers

`bin/magento phlpdtrt:multi-process-catalog:images:resize 4`

this command would start 4 workers resizing product images in parallel. This is a very cpu intense task, so it makes absolutely no sense to use more worker than cpu cores you have.


#### stopping the worker

the best way to stop the worker is to kill the master process (the console command). Shortly after the process is terminated, the worker will also stop the execution

