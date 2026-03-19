const ldap = require('ldapjs');

const LDAP_URL = process.env.LDAP_URL || 'ldap://192.168.1.106:389';
const LDAP_BASE_DN = process.env.LDAP_BASE_DN || 'ou=people,dc=slam,dc=lab';

/**
 * Tente un bind LDAP avec le uid et le mot de passe fournis.
 * @param {string} uid - Identifiant LDAP (ex: jean.dupont)
 * @param {string} password - Mot de passe
 * @returns {Promise<boolean>} true si le bind a reussi, false sinon
 */
function bindUser(uid, password) {
  return new Promise((resolve) => {
    const client = ldap.createClient({ url: LDAP_URL });
    const dn = `uid=${uid},${LDAP_BASE_DN}`;

    client.bind(dn, password, (err) => {
      client.unbind(() => {});
      if (err) {
        resolve(false);
      } else {
        resolve(true);
      }
    });

    client.on('error', () => {
      resolve(false);
    });
  });
}

module.exports = { bindUser };
