describe('Formulaire de Connexion', () => {
  it('connexion OK', () => {
    cy.visit('/login');
    cy.get('#username').type('test@example.com');
    cy.get('#password').type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/');
    cy.contains('Bienvenue').should('exist');
  });

  it('connexion KO', () => {
    cy.visit('/login');
    cy.get('#username').type('test@example.com');
    cy.get('#password').type('wrongpassword');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/login');
  });
});
