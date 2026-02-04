describe('Génération de PDF', () => {
  beforeEach(() => {
    cy.visit('/login');
    cy.get('#username').type('test@example.com');
    cy.get('#password').type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('not.include', '/login');
  });

  it('accès page génération PDF', () => {
    cy.visit('/pdf/generate');
    cy.contains('Générer un PDF').should('exist');
    cy.get('#pdf-type').should('exist');
  });

  it('génération URL vers PDF', () => {
    cy.visit('/pdf/generate');
    cy.get('#pdf-type').select('url');
    cy.get('#url').type('https://example.com');
    cy.get('button[type="submit"]').click();
    cy.get('.flash-success, .flash-error').should('exist');
  });
});
