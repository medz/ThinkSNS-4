const { prisma } = require('../helpers');

exports.authTokenValidityPeriodSettings = () => {
  const key = {
    namespace: 'system',
    name: 'auth token validity period key',
  };
  return prisma.setting.upsert({
    where: {
      namespace_name: key,
    },
    create: {
      ...key,
      value: {
        expiredIn: {
          value: 1,
          unit: 'days',
        },
        refreshExpiredIn: {
          value: 1,
          unit: 'months',
        },
      },
    },
    update: {},
  });
};
