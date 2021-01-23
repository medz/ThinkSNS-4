import { Injectable } from '@nestjs/common';
import { ID } from '@socfony/kernel';
import { PrismaClient, Prisma, SecurityCode } from '@socfony/prisma';
import { TencentCloudSmsService } from '@socfony/tencent-cloud-sms';

@Injectable()
export class SecurityCodeService {
  constructor(
    private readonly prisma: PrismaClient,
    private readonly sms: TencentCloudSmsService,
  ) {}

  /**
   * Get phone send template options.
   * @param hasChina If `true` get China options.
   */
  getOptionsSms(hasChina: boolean) {
    const { china, other, expiredIn } = this.getOptions();
    return Object.assign({}, hasChina ? china : other, { expiredIn });
  }

  getOptions() {
    return {
      expiredIn: Number.parseInt(
        process.env.TENCENT_CLOUD_SMS_AUTHORIZATION_EXPIRED_IN || '300',
      ),

      china: {
        templateId:
          process.env.TENCENT_CLOUD_SMS_AUTHORIZATION_CHINA_TEMPLATE_ID,
        veriables: process.env.TENCENT_CLOUD_SMS_AUTHORIZATION_CHINA_VERIABLES.split(
          ',',
        ),
      },

      other: {
        templateId:
          process.env.TENCENT_CLOUD_SMS_AUTHORIZATION_OTHER_TEMPLATE_ID,
        veriables: process.env.TENCENT_CLOUD_SMS_AUTHORIZATION_OTHER_VERIABLES.split(
          ',',
        ),
      },
    };
  }

  /**
   * Send security code to account.
   * @param data Security code create input.
   */
  async send(
    data: Omit<
      Prisma.SecurityCodeCreateInput,
      'id' | 'createdAt' | 'response' | 'code'
    >,
  ) {
    const security = await this.prisma.securityCode.create({
      data: {
        ...data,
        id: ID.generator(32),
        code: ID.numeralGenerator(6),
      },
    });
    this._sendSecurityCodeForTencentCloud(security);

    return security;
  }

  /**
   * Find lts security code for account and code.
   * @param account Sender account.
   * @param code Sent code.
   */
  findFirst(account: string, code: string = undefined) {
    return this.prisma.securityCode.findFirst({
      where: { account, code },
    });
  }

  /**
   * validate security don't expired.
   * @param security security code object.
   */
  async validateSecurity(security: SecurityCode) {
    if (!security || security.disabledAt) return true;
    const { expiredIn } = this.getOptions();
    const value = (Date.now() - security.createdAt.getTime()) / 1000;
    return value > expiredIn;
  }

  /**
   * disable a security code.
   * @param security security code object.
   */
  async disableSecurity(security: SecurityCode) {
    if (!security || security.disabledAt) return security;
    return await this.prisma.securityCode.update({
      where: { id: security.id },
      data: { disabledAt: new Date() },
    });
  }

  /**
   * Using Tencent Cloud SMS client send security code.
   * @param security security code object.
   */
  private async _sendSecurityCodeForTencentCloud(security: SecurityCode) {
    const setting = this.getOptionsSms(security.account.startsWith('+86'));
    const params = setting.veriables.map((value) =>
      value
        .replace('#code#', security.code)
        .replace('#expired#', (setting.expiredIn / 60).toFixed(0)),
    );
    let result: any;
    try {
      result = await this.sms.send({
        PhoneNumberSet: [security.account],
        TemplateID: setting.templateId,
        TemplateParamSet: params,
      });
    } catch (e) {
      result = e;
    }

    await this.prisma.securityCode.update({
      where: { id: security.id },
      data: {
        response: result,
      },
    });
  }
}
