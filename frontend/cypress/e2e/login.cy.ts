describe('Login Flow', () => {
  it('visits login page and logs in with valid credentials', () => {
    cy.visit('/login')

    // Fill in the form
    cy.get('input[placeholder="Usuário"]').type('admin')
    cy.get('input[placeholder="Senha"]').type('admin123')

    // Submit
    cy.contains('button', 'Entrar').click()

    // Should redirect to dashboard
    cy.url().should('include', '/dashboard')
    cy.contains('h1', 'Relatórios').should('be.visible')
  })

  it('shows error for invalid credentials', () => {
    cy.visit('/login')

    cy.get('input[placeholder="Usuário"]').type('wrong')
    cy.get('input[placeholder="Senha"]').type('wrong')
    cy.contains('button', 'Entrar').click()

    cy.contains('Credenciais inválidas').should('be.visible')
  })
})