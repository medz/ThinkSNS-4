import { Injectable } from '@nestjs/common';
import { SecuritySmsCode, User } from '@prisma/client';
import { PasswordHelper } from 'src/helper';
import { SecuritySmsService } from 'src/security/security-sms.service';
import { UserSecurityCompareType } from './enums';

/**
 * User service.
 */
@Injectable()
export class UserService {
  /**
   * Create user service.
   * @param securitySmsService Security SMS service.
   */
  constructor(private readonly securitySmsService: SecuritySmsService) {}

  /**
   * Compore user password.
   * @param user Need compare password user.
   * @param password input password.
   */
  async comparePassword(user: User, password: string): Promise<boolean> {
    if (!user?.password) return false;
    return await PasswordHelper.compare(password, user.password);
  }

  /**
   * Compare secuity.
   * @param user Need compare user.
   * @param type compare type.
   * @param security Security object.
   */
  async compareSecurity(
    user: User,
    type: UserSecurityCompareType,
    security: string,
  ): Promise<boolean | (() => Promise<SecuritySmsCode>)> {
    switch (type) {
      case UserSecurityCompareType.PASSWORD:
        return await this.comparePassword(user, security);
      case UserSecurityCompareType.PHONE_SMS_CODE:
        return await this.securitySmsService.compareCodeWithUsedFn(
          user.phone,
          security,
        );
      default:
        return false;
    }
  }
}
