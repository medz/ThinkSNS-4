import * as dayjs from 'dayjs';
import * as bcrypt from 'bcrypt';
import { ID } from '@socfony/kernel';
import {
  AuthorizationToken,
  Prisma,
  PrismaClient,
  User,
} from '@socfony/prisma';
import {
  AUTH_TOKEN_DEFAULT_EXPORED_IN,
  AUTH_TOKEN_DEFAULT_EXPORED_UNIT,
  AUTH_TOKEN_DEFAULT_REFRESH_EXPORED_IN,
  AUTH_TOKEN_DEFAULT_REFRESH_EXPORED_UNIT,
  AUTH_TOKEN_VVALIDITY_PERIOD_KEY,
} from './constants';
import { HasTokenExpiredType } from './enums';
import { AuthorizationTokenValidityPeriod } from './types';
import {
  AUTHORIZATION_TOKEN_NOT_FOUND,
  SECURITY_VALIDATE_ERROR,
  USER_NOT_FOUND,
  USER_NOT_SET_PASSWORD,
  USER_PASSWORD_NOT_COMPARE,
} from '@socfony/error-code';
import { SecurityCodeService } from '@socfony/security-code';
import { Injectable } from '@nestjs/common';

@Injectable()
export class AuthService {
  constructor(
    private readonly prisma: PrismaClient,
    private readonly securityCodeService: SecurityCodeService,
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
        value: expiredIn.value || AUTH_TOKEN_DEFAULT_EXPORED_IN,
        unit: expiredIn.unit || AUTH_TOKEN_DEFAULT_EXPORED_UNIT,
      },
      refreshExpiredIn: {
        value: refreshExpiredIn.value || AUTH_TOKEN_DEFAULT_REFRESH_EXPORED_IN,
        unit: refreshExpiredIn.unit || AUTH_TOKEN_DEFAULT_REFRESH_EXPORED_UNIT,
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
  async createAuthorizationTokenForUser(
    user: User | string,
  ): Promise<AuthorizationToken> {
    const setting = await this.getTokenExpiredIn();
    return await this.prisma.authorizationToken.create({
      data: {
        userId: typeof user === 'string' ? user : user.id,
        token: ID.generator(128),
        expiredAt: dayjs()
          .add(setting.expiredIn.value, setting.expiredIn.unit)
          .toDate(),
        refreshExpiredAt: dayjs()
          .add(setting.refreshExpiredIn.value, setting.refreshExpiredIn.unit)
          .toDate(),
      },
    });
  }

  /**
   * Using user password login and create authorization token object.
   * @param where User query where input.
   * @param password User password string.
   */
  async loginWithPassword(
    where: Prisma.UserWhereUniqueInput,
    password: string,
  ) {
    const user = await this.prisma.user.findUnique({
      where,
      rejectOnNotFound: () => Error(USER_NOT_FOUND),
    });
    if (!user.password) {
      throw new Error(USER_NOT_SET_PASSWORD);
    }

    const hasMatch = await bcrypt.compare(password, user.password);
    if (hasMatch) {
      return await this.createAuthorizationTokenForUser(user);
    }

    throw new Error(USER_PASSWORD_NOT_COMPARE);
  }

  /**
   * Using security code login and create authorization token object.
   * @param where User query where input.
   * @param code User login security code.
   */
  async loginWithSecurityCode(
    where: Prisma.UserWhereUniqueInput,
    code: string,
  ) {
    const account = Object.values(where).pop();
    const security = await this.securityCodeService.findFirst(account, code);
    if (await this.securityCodeService.validateSecurity(security)) {
      throw new Error(SECURITY_VALIDATE_ERROR);
    }
    let user = await this.prisma.user.findUnique({ where });
    if (!user) {
      const { email, phone } = where;
      user = await this.prisma.user.create({
        data: {
          email,
          phone,
          id: ID.generator(32),
        },
      });
    }
    this.securityCodeService.disableSecurity(security);

    return this.createAuthorizationTokenForUser(user);
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
          token: ID.generator(128),
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
      const [value] = await this.prisma.$transaction([
        this.createAuthorizationTokenForUser(authorizationToken.userId),
        this.prisma.authorizationToken.update({
          where: { token: authorizationToken.token },
          data: {
            expiredAt: fiveMinutesDate,
            refreshExpiredAt: new Date(),
          },
        }),
      ]);
      return value;
    }

    return this.createAuthorizationTokenForUser(authorizationToken.userId);
  }
}
