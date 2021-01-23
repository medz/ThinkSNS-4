const path = require('path');
const fs = require('fs');
const { packagesNames, dependencies } = require('./install');

function dependencieFiliter(dependencies) {
  const result = {};
  Object.keys(dependencies)
    .filter((value) => !packagesNames.includes(value))
    .forEach((name) => {
      result[name] = dependencies[name];
    });
  return result;
}

const pkg = require('../package.json');
pkg.dependencies = dependencieFiliter(
  Object.assign({}, dependencies, pkg.dependencies),
);

fs.writeFileSync(
  path.join(__dirname, '../package.json'),
  JSON.stringify(pkg, null, 2),
);
