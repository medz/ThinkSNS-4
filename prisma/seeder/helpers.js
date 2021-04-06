const { PrismaClient } = require('@prisma/client');
const { readdirSync } = require('fs');
const { join } = require('path');
const { seeder: defaultUserSeeder } = require('./seeders/user.seeder');

const _$suffix = '.seeder.js';
const _$seederRunnerName = 'seeder';
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
exports.findSeeders = (context) =>
  readdirSync(context)
    .filter((filename) => filename.endsWith(_$suffix))
    .map((filename) => {
      const { [_$seederRunnerName]: main } = require(join(context, filename));
      return main;
    });

exports.defaultUser = defaultUserSeeder;
