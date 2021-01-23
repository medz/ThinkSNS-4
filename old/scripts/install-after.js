const path = require('path');
const fs = require('fs');
const { dependencies } = require('./install');
const pkg = require('../package.json');

const result = {};
Object.keys(dependencies).forEach((key) => {
  if (!Object.keys(pkg.dependencies).includes(key)) {
    result[key] = pkg.dependencies[key];
  }
});
pkg.dependencies = result;

fs.writeFileSync(
  path.join(__dirname, '../package.json'),
  JSON.stringify(pkg, null, 2),
);
