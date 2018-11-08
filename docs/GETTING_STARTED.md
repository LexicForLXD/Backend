# Getting started

## Register host

1. Add the host to the system
2. Copy the public ssh key to the host's authorized_keys file

## Create container

Make sure you imported all nessacary things (storage pools, images).
If you don't have any images, please create a new image.

## Create image

1. Decide whether you want to create the image from a local container, remote image or (tarball NOT YET IMPLEMENTED).
2. Make the request. See [here](https://github.com/lxc/lxd/blob/master/doc/rest-api.md) for more details.

## Import

You can import most things from the lxd-host into lexic. To import from a host, make sure you have at least one authenticated host added to lexic. You can either import everything at once or only selected items.
