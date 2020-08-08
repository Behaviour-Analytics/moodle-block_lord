The Munkres algorithm implementation was taken from
https://github.com/addaleax/munkres-js. The package was last altered in 2017,
so there may not be any new versions available. The algorithm is wrapped as
an AMD module in amd/src/modules.js, where it can be readily available to the
plugin.

Should the package need to be upgraded or replaced, the wrapped code in the
amd/src/modules.js file will need to be changed and then minified. The code in
this directory would also need to be updated or replaced and changes made to the
thirdpartylibs.xml file.
