const { nanoid } = require('nanoid');
const { hash, genSalt } = require('bcrypt');
const { prisma } = require('../helpers');

const _$defaultUsername = 'socfony';

exports.defaultUser = async () => {
  const user = await prisma.user.findUnique({
    where: { username: _$defaultUsername },
    rejectOnNotFound: false,
  });
  if (user) {
    return user;
  }

  const salt = await genSalt(10, 'b');
  const password = await hash('socfony', salt);

  return await prisma.user.create({
    data: {
      id: nanoid(64),
      username: _$defaultUsername,
      password,
    },
  });
};
