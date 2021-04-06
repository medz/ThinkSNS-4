const { nanoid } = require('nanoid');
const { hash, genSalt } = require('bcrypt');
const helpers = require('../helpers');

const _$defaultUsername = 'socfony';

exports.seeder = async () => {
  const user = await helpers.prisma.user.findUnique({
    where: { username: _$defaultUsername },
    rejectOnNotFound: false,
  });
  if (user) {
    return user;
  }

  const salt = await genSalt(10, 'b');
  const password = await hash('socfony', salt);

  return await helpers.prisma.user.create({
    data: {
      id: nanoid(64),
      username: _$defaultUsername,
      password,
    },
  });
};
