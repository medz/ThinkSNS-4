const { projects } = require('../nest-cli.json');
const path = require('path');

const packages = Object.values(projects).map((project) =>
  path.resolve(__dirname, '../', project.root, 'package.json'),
);

module.exports.packagesNames = Object.keys(projects).map(
  (name) => `@socfony/${name}`,
);

module.exports.dependencies = packages
  .map((filename) => require(filename))
  .map((package) =>
    Object.assign(
      {},
      package.dependencies,
      package.peerDependencies,
      package.devDependencies,
    ),
  )
  .reduce(
    (dependencies, projectDependencies) =>
      Object.assign({}, dependencies, projectDependencies),
    {},
  );
