import { Injectable } from '@nestjs/common';
import { AuthorizationToken, PrismaClient, User } from '@prisma/client';
import * as dayjs from 'dayjs';
import {
  AUTHORIZATION_TOKEN_NOT_FOUND,
  SECURITY_COMPARE_FAILED,
} from 'src/constants';
import { IDHelper } from 'src/helper';
import { UserService } from 'src/user/user.service';
import { UserSecurityCompareType } from 'src/user/enums';
import {
  AUTH_TOKEN_DEFAULT_EXPIRED_IN,
  AUTH_TOKEN_DEFAULT_EXPIRED_UNIT,
  AUTH_TOKEN_DEFAULT_REFRESH_EXPIRED_IN,
  AUTH_TOKEN_DEFAULT_REFRESH_EXPIRED_UNIT,
  AUTH_TOKEN_VVALIDITY_PERIOD_KEY,
} from './constants';
import { HasTokenExpiredType } from './enums';
import { AuthorizationTokenValidityPeriod } from './types';

/**
 * Authorization token service.
 */
@Injectable()
export class AuthorizationTokenService {
  /**
   * Create Authotization token service.
   * @param prisma Prisma client.
   * @param userService User service.
   */
  constructor(
    private readonly prisma: PrismaClient,
    private readonly userService: UserService,
  ) {}

  /**
   * Get token expired in validity period.
   */
  async getTokenExpiredIn(): Promise<AuthorizationTokenValidityPeriod> {
    const setting = await this.prisma.setting.findUnique({
      where: {
        namespace_name: {
          namespace: 'system',
          name: AUTH_TOKEN_VVALIDITY_PERIOD_KEY,
        },
      },
      rejectOnNotFound: false,
    });
    const { value = {} } = setting || {};
    const { expiredIn = {}, refreshExpiredIn = {} } = value as any;

    return {
      expiredIn: {
        value: expiredIn.value || AUTH_TOKEN_DEFAULT_EXPIRED_IN,
        unit: expiredIn.unit || AUTH_TOKEN_DEFAULT_EXPIRED_UNIT,
      },
      refreshExpiredIn: {
        value: refreshExpiredIn.value || AUTH_TOKEN_DEFAULT_REFRESH_EXPIRED_IN,
        unit: refreshExpiredIn.unit || AUTH_TOKEN_DEFAULT_REFRESH_EXPIRED_UNIT,
      },
    };
  }

  /**
   * Has authorization token expired.
   * @param client `AuthotizationToken` query Prisma client.
   * @param type Validate type.
   */
  hasTokenExpired(
    token: AuthorizationToken,
    type: HasTokenExpiredType,
  ): boolean {
    if (!token) return true;
    const expiredAt =
      type === HasTokenExpiredType.AUTH
        ? token.expiredAt
        : token.refreshExpiredAt;

    return !expiredAt || expiredAt.getTime() <= Date.now();
  }

  /**
   * Create authorization token object for user.
   * @param user Create authoprization token object user.
   */
  async createUserAuthorizationToken(
    user: User | string,
  ): Promise<AuthorizationToken> {
    const setting = await this.getTokenExpiredIn();
    const [token] = await this.prisma.$transaction([
      this.prisma.authorizationToken.create({
        data: {
          userId: typeof user === 'string' ? user : user.id,
          token: IDHelper.id(128),
          expiredAt: dayjs()
            .add(setting.expiredIn.value, setting.expiredIn.unit)
            .toDate(),
          refreshExpiredAt: dayjs()
            .add(setting.refreshExpiredIn.value, setting.refreshExpiredIn.unit)
            .toDate(),
        },
      }),
      this.prisma.authorizationToken.deleteMany({
        where: {
          refreshExpiredAt: {
            lte: dayjs().add(10, 'minutes').toDate(),
          },
        },
      }),
    ]);

    return token;
  }

  /**
   * Create token with security.
   * @param user Creathe token with user.
   * @param type creat compare security type.
   * @param security security object.
   */
  async createTokenWithSecurity(
    user: User,
    type: UserSecurityCompareType,
    security: string,
  ) {
    const compared = await this.userService.compareSecurity(
      user,
      type,
      security,
    );
    if (compared) {
      if (compared instanceof Function) {
        compared();
      }
      return this.createUserAuthorizationToken(user);
    }

    throw new Error(SECURITY_COMPARE_FAILED);
  }

  /**
   * Refresh authorization token object to database.
   * @param authorizationToken Await refresh authorization token object.
   */
  async refreshAuthorizationToken(
    authorizationToken: AuthorizationToken,
  ): Promise<AuthorizationToken> {
    const setting = await this.getTokenExpiredIn();
    // If authorization token not found, throw `not found` error.
    if (!authorizationToken) {
      throw new Error(AUTHORIZATION_TOKEN_NOT_FOUND);

      // If token is expired, update to new token.
    } else if (authorizationToken.expiredAt.getTime() <= Date.now()) {
      return await this.prisma.authorizationToken.update({
        where: { token: authorizationToken.token },
        data: {
          token: IDHelper.id(128),
          expiredAt: dayjs()
            .add(setting.expiredIn.value, setting.expiredIn.unit)
            .toDate(),
          refreshExpiredAt: dayjs()
            .add(setting.refreshExpiredIn.value, setting.refreshExpiredIn.unit)
            .toDate(),
        },
      });
    }

    // If the token validity period is greater than 5 minutes,
    // set it to expire in three minutes, and then create a new token
    const fiveMinutesDate = dayjs().add(5, 'minutes').toDate();
    if (authorizationToken.expiredAt.getTime() > fiveMinutesDate.getTime()) {
      await this.prisma.authorizationToken.update({
        where: { token: authorizationToken.token },
        data: {
          expiredAt: fiveMinutesDate,
          refreshExpiredAt: new Date(),
        },
      });
    }

    return this.createUserAuthorizationToken(authorizationToken.userId);
  }
}
