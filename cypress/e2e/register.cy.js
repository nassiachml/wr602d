describe('Création de compte', () => {
  it('création valide', () => {
    cy.visit('/register');
    const email = 'newuser' + Date.now() + '@test.com';
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type('password123');
    cy.get('select[name="subscription"]').select(1);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/login');
    cy.contains('Inscription réussie').should('exist');
  });
});
