import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ArticleInput, ArticleOptions, StationArticle } from '../models/article.model';
import { ApiService } from './api.service';
@Injectable({providedIn:'root'})
export class ArticleService {
  constructor(private api:ApiService) {}
  options():Observable<ArticleOptions>{return this.api.get('station-articles/options')}
  list(station:number):Observable<StationArticle[]>{return this.api.get('station-articles',{station})}
  create(data:ArticleInput):Observable<StationArticle>{return this.api.post('station-articles',data)}
  update(id:number,data:ArticleInput):Observable<StationArticle>{return this.api.put(`station-articles/${id}`,data)}
  createCategory(data:{name:string;code:string}):Observable<{id:number;name:string}>{return this.api.post('station-articles/categories',data)}
  deactivate(id:number):Observable<StationArticle>{return this.api.patch(`station-articles/${id}/deactivate`,{})}
}
