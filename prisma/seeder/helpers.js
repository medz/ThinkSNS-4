const { PrismaClient } = require('@prisma/client');
const { readdirSync } = require('fs');
const { join } = require('path');

const _$suffix = '.seeder.js';
const _$prisma = new PrismaClient();

/**
 * root seeders context.
 */
exports.rootSeedersContext = join(__dirname, 'seeders');

/**
 * Get database connection.
 */
exports.prisma = _$prisma;

/**
 * Use context find seeders.
 * @param {string} context seeder path context.
 * @returns {Array<Function>} Seeders.
 */
exports.findSeeders = (context) => {
  const seederFilenames = readdirSync(context).filter((filename) =>
    filename.endsWith(_$suffix),
  );

  const seeders = [];
  for (const seederFilename of seederFilenames) {
    const _$seeders = require(join(context, seederFilename));
    seeders.push(...Object.values(_$seeders));
  }

  return seeders;
};
