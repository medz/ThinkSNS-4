import { customAlphabet, nanoid } from 'nanoid';

/**
 * ID helper.
 */
export namespace IDHelper {
  /**
   * Generate a custom alphabet ID.
   * @param alphabet custom alphabet
   * @param size Size of the ID.
   */
  export const custom = (alphabet: string) => (size: number): string =>
    customAlphabet(alphabet, size)();

  /**
   * Generate secure URL-friendly unique ID
   * @param size Size of the ID.
   */
  export const id = (size: number): string => nanoid(size);
  /**
   * Generate numeral ID.
   * @param size Size of the ID.
   */
  export const numeral = (size: number): string => custom('0123456789')(size);

  /**
   * Generate alphabet(a-z) ID.
   * @param size Size of the ID.
   */
  export const alphabet = (size: number): string =>
    custom('qwertyuiopasdfghjklzxcvbnm')(size);

  /**
   * Generate readability(23456789qwertyupasdfghjklzxcvbnm) ID.
   * @param size Size of the ID.
   */
  export const readability = (size: number): string =>
    custom('23456789qwertyupasdfghjklzxcvbnm')(size);
}
