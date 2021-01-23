import * as bcrypt from 'bcrypt';
import { Injectable } from '@nestjs/common';
import { Prisma, PrismaClient, User, UserAccountChangeState } from '@socfony/prisma';
import { USER_LOGIN_EXISTS, USER_NOT_FOUND } from '@socfony/error-code';
import { ID } from '@socfony/kernel';

@Injectable()
export class UserService {
  constructor(private readonly prisma: PrismaClient) {}

  async stateResolve(user: User | string): Promise<UserAccountChangeState> {
    const userId = typeof user === 'string' ? user : user.id;
    const state = this.prisma.userAccountChangeState.findUnique({
      where: { userId },
      rejectOnNotFound: false,
    });
    if (state) return state;
    return await this.prisma.userAccountChangeState.create({
      data: {
        userId,
        id: ID.generator(32),
      },
    });
  }

  async updateLogin(user: User | string, login: string): Promise<User> {
    const userId = typeof user === 'string' ? user : user.id;
    const other = await this.prisma.user.findUnique({
      where: { login },
      rejectOnNotFound: false,
    });
    if (other && userId !== other.id) {
      throw new Error(USER_LOGIN_EXISTS);
    }
    const state = await this.stateResolve(user);
    const [value] = await this.prisma.$transaction([
      this.prisma.user.update({
        where: { id: userId },
        data: { login },
      }),
      this.prisma.userAccountChangeState.update({
        where: { id: state.id },
        data: { loginChangeAt: new Date() },
      }),
    ]);

    return value;
  }

  async passwordCompare(user: User | string, password: string): Promise<boolean> {
    const _user = await this.resolveUser(user);
    if (_user.password) {
        return await bcrypt.compare(_user.password, password);
    }

    return false;
  }

  async resolveUser(user: User | string, args: Pick<Prisma.UserFindUniqueArgs, "select" | "include"> = {}): Promise<User> {
      if (typeof user === 'string') {
          return await this.prisma.user.findUnique({
            ...args,
            where: { id: user },
            rejectOnNotFound: () => Error(USER_NOT_FOUND),
          });
      }

      return user;
  }
}
