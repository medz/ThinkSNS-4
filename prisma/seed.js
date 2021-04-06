const { findSeeders, rootSeedersContext, prisma } = require('./seeder/helpers');

async function main() {
  const seeders = findSeeders(rootSeedersContext);
  const result = [];
  for await (const seeder of seeders) {
    result.push(await seeder());
  }

  return result;
}

main()
  .catch((err) => {
    console.error(err);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
