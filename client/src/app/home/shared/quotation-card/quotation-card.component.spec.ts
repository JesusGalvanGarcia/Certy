import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QuotationCardComponent } from './quotation-card.component';

describe('QuotationCardComponent', () => {
  let component: QuotationCardComponent;
  let fixture: ComponentFixture<QuotationCardComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QuotationCardComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QuotationCardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
