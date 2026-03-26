# jPOS 2.x User Guide

## Overview
This directory contains the complete user guide documentation for the jPOS 2.x system.

## Structure
```
user-guide/
├── index.html              # Main user guide page
├── css/
│   └── style.css          # Styling for the user guide
├── screenshots/           # Directory for application screenshots
└── README.md             # This file
```

## Adding Screenshots

To add screenshots to the user guide:

1. Take screenshots of your application
2. Save them in the `screenshots/` directory
3. Use descriptive names that match the placeholders in `index.html`

### Recommended Screenshot Names:
- `login-page.png` - Login interface
- `dashboard.png` - Main dashboard view
- `add-product.png` - Add product form
- `product-list.png` - Products listing page
- `categories.png` - Categories management
- `brands.png` - Brands management
- `units.png` - Measurement units
- `pos-interface.png` - Point of Sale interface
- `sales-invoices.png` - Sales invoices list
- `customers.png` - Customer management
- `grn-create.png` - Create GRN form
- `grn-returns.png` - GRN returns page
- `suppliers.png` - Supplier management
- `transfer-request.png` - Transfer request form
- `prn.png` - Product Release Note
- `sales-report.png` - Sales report
- `stock-report.png` - Stock report
- `movement-report.png` - Product movement report
- `expense-report.png` - Expense report
- `company-settings.png` - Company information settings
- `app-settings.png` - Application settings
- `bill-settings.png` - Bill/Invoice settings

## Screenshot Guidelines

For best results, follow these guidelines when capturing screenshots:

1. **Resolution**: Use at least 1920x1080 resolution
2. **Format**: Save as PNG for better quality
3. **Content**: Ensure no sensitive data is visible
4. **Clarity**: Make sure text is readable
5. **Consistency**: Use consistent window sizes
6. **Annotations**: Use arrows or highlights if needed

## Accessing the User Guide

The user guide can be accessed in two ways:

1. **Direct URL**: Navigate to `/user-guide/` in your browser
   - Example: `http://localhost:8000/user-guide/`

2. **From Application**: Add a link in your application navigation
   ```html
   <a href="/user-guide/" target="_blank">User Guide</a>
   ```

## Customization

### Updating Company Information
Edit `index.html` and update the following sections:
- Company name in the header
- Contact information in the contact section
- Version numbers
- Support email and phone numbers

### Styling Changes
Modify `css/style.css` to change:
- Colors (see CSS variables in `:root`)
- Fonts
- Layout dimensions
- Responsive breakpoints

### Adding New Sections
To add new sections to the guide:

1. Add a new navigation item in the sidebar:
```html
<li><a href="#new-section">New Section</a></li>
```

2. Add the section content in the main area:
```html
<section id="new-section" class="section">
    <h2>New Section Title</h2>
    <!-- Content here -->
</section>
```

## Features

✓ Responsive design (works on desktop, tablet, mobile)
✓ Smooth scrolling navigation
✓ Print-friendly layout
✓ Screenshot placeholders with fallback display
✓ Searchable content (Ctrl+F)
✓ Organized sections with clear hierarchy
✓ Professional styling with consistent branding

## Maintenance

Regular updates recommended:
- Update screenshots when UI changes
- Add new features to relevant sections
- Update version number and date
- Review FAQ based on user feedback
- Update troubleshooting section with common issues

## Technical Details

- **Technology**: Pure HTML5 + CSS3 + Vanilla JavaScript
- **Dependencies**: None (uses Google Fonts CDN only)
- **Browser Support**: All modern browsers
- **Mobile Friendly**: Yes, fully responsive
- **Print Support**: Optimized for printing

## License

This user guide is part of the jPOS 2.x system.

---

Last Updated: December 2025
