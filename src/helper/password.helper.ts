import {
  compare as comparePassword,
  hash as createPasswordHash,
  genSalt,
} from 'bcrypt';

/**
 * Password helper.
 */
export namespace PasswordHelper {
  /**
   * Compare password
   * @param password password text.
   * @param encrypted encrpyted password text.
   */
  export function compare(password: string, encrypted: string) {
    return comparePassword(password, encrypted);
  }

  /**
   * Create a password encrpyted hash text.
   * @param password password text.
   */
  export async function hash(password: string) {
    const salt = await genSalt(10, 'b');
    return await createPasswordHash(password, salt);
  }
}
